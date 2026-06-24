<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Checkout\Session;
use Stripe\Invoice;
use App\Models\User;
use App\Models\Coupon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StripeSubscriptionService
{
    protected StripeCustomerService $customerService;

    public function __construct(StripeCustomerService $customerService)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $this->customerService = $customerService;
    }

    /**
     * Create a subscription for a user
     * @param string $plan starter|pro|business
     * @param string|null $couponCode optional coupon code
     * @param string $locale fr|nl
     */
    public function createSubscription(User $user, string $plan, ?string $couponCode = null, string $locale = 'fr'): array
    {
        return DB::transaction(function () use ($user, $plan, $couponCode, $locale) {
            $plans = config('subscriptions.plans');
            if (!isset($plans[$plan])) {
                throw new \InvalidArgumentException("Invalid plan: $plan");
            }

            $planConfig = $plans[$plan];
            $priceKey = "stripe_price_id_" . $locale;
            $priceId = $planConfig[$priceKey];

            if (!$priceId) {
                throw new \Exception("Missing Stripe price ID for plan $plan locale $locale");
            }

            // Get or create Stripe customer
            $customer = $this->customerService->getOrCreateCustomer($user);

            // Build line items for Checkout Session
            // Only include the base subscription price in checkout
            // Metered usage will be added automatically to the subscription
            $lineItems = [
                [
                    'price' => $priceId,
                    'quantity' => 1,
                ],
            ];

            // Prepare discounts if coupon provided
            $discounts = [];
            if ($couponCode) {
                // Validate coupon first
                $coupon = Coupon::where('code', $couponCode)
                    ->where('active', true)
                    ->first();

                if (!$coupon || $coupon->isExpired()) {
                    throw new \Exception("Coupon is invalid or expired");
                }

                // Check max users
                if ($coupon->max_users) {
                    $usedCount = $coupon->usages()->distinct('user_id')->count();
                    if ($usedCount >= $coupon->max_users) {
                        throw new \Exception("Coupon usage limit reached");
                    }
                }

                if ($coupon->stripe_coupon_id) {
                    $discounts[] = [
                        'coupon' => $coupon->stripe_coupon_id,
                    ];
                }
            }

            // Create Stripe Checkout Session
            $sessionParams = [
                'customer' => $customer->id,
                'line_items' => $lineItems,
                'mode' => 'subscription',
                'success_url' => url('/subscription/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url('/buy-credits'),
                'billing_address_collection' => 'required',
                'customer_update' => [
                    'address' => 'auto',
                    'name' => 'auto',
                ],
                'tax_id_collection' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'user_id' => $user->id,
                    'plan' => $plan,
                    'coupon_code' => $couponCode ?? '',
                ],
            ];

            if (!empty($discounts)) {
                $sessionParams['discounts'] = $discounts;
            }

            $session = Session::create($sessionParams);

            // Update user with pending subscription status
            $user->update([
                'subscription_plan' => $plan,
                'subscription_status' => 'pending_payment',
                'applied_coupon_code' => $couponCode,
                'coupon_applied_at' => $couponCode ? now() : null,
            ]);

            // Log event
            $user->subscriptionEvents()->create([
                'event_type' => 'checkout_started',
                'plan' => $plan,
                'payload' => [
                    'session_id' => $session->id,
                    'coupon' => $couponCode,
                ],
            ]);

            return [
                'session_id' => $session->id,
                'checkout_url' => $session->url,
                'status' => 'pending_payment',
            ];
        });
    }

    /**
     * Upgrade or downgrade a subscription
     */
    public function updateSubscription(User $user, string $newPlan, string $locale = 'fr'): void
    {
        if (!$user->stripe_subscription_id) {
            throw new \Exception("User has no active subscription");
        }

        $plans = config('subscriptions.plans');
        if (!isset($plans[$newPlan])) {
            throw new \InvalidArgumentException("Invalid plan: $newPlan");
        }

        $planConfig = $plans[$newPlan];
        $priceKey = "stripe_price_id_" . $locale;
        $newPriceId = $planConfig[$priceKey];

        if (!$newPriceId) {
            throw new \Exception("Missing Stripe price ID");
        }

        // Retrieve current subscription
        $subscription = Subscription::retrieve($user->stripe_subscription_id);

        // Find the base plan item
        $baseItem = null;
        foreach ($subscription->items->data as $item) {
            // Check if this is the base plan price (not metered)
            // Metered prices have usage_type = 'metered', standard recurring prices have usage_type = null
            $usageType = $item->price->recurring->usage_type ?? null;
            if ($usageType !== 'metered') {
                $baseItem = $item;
                break;
            }
        }

        if (!$baseItem) {
            throw new \Exception("Could not find base plan item in subscription");
        }

        // Update subscription
        $isUpgrade = $plans[$newPlan]['monthly_price'] > $plans[$user->subscription_plan]['monthly_price'];

        $updateParams = [
            'items' => [
                [
                    'id' => $baseItem->id,
                    'price' => $newPriceId,
                ],
            ],
            'metadata' => [
                'plan' => $newPlan,
            ],
        ];

        $updateParams['proration_behavior'] = 'none';
        $updateParams['billing_cycle_anchor'] = 'now';

        $updatedSubscription = Subscription::update($user->stripe_subscription_id, array_merge($updateParams, [
            'expand' => ['latest_invoice.payment_intent'],
        ]));

        if (!empty($updatedSubscription->latest_invoice)) {
            $latestInvoice = $updatedSubscription->latest_invoice;
            if ($latestInvoice->status !== 'paid' && $latestInvoice->amount_due > 0) {
                Invoice::retrieve($latestInvoice->id)->pay();
            }
        }

        $user->update(['subscription_plan' => $newPlan]);

        $user->subscriptionEvents()->create([
            'event_type' => $isUpgrade ? 'upgraded' : 'downgraded',
            'plan' => $newPlan,
        ]);
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(User $user, bool $immediate = false): void
    {
        if (!$user->stripe_subscription_id) {
            throw new \Exception("User has no active subscription");
        }

        $sub = Subscription::retrieve($user->stripe_subscription_id);

        if ($immediate) {
            $sub->cancel();
            // Immediate cancel - mark as canceled
            $user->update([
                'subscription_status' => 'canceled',
                'subscription_ends_at' => now(),
            ]);
        } else {
            $sub = Subscription::update($user->stripe_subscription_id, [
                'cancel_at_period_end' => true,
            ]);
            // Scheduled cancel - mark as active but set end date
            $user->update([
                'subscription_status' => 'active', // Still active until period ends
                'subscription_ends_at' => $sub->cancel_at ? \Carbon\Carbon::createFromTimestamp($sub->cancel_at) : null,
            ]);
        }

        $user->subscriptionEvents()->create([
            'event_type' => 'canceled',
            'plan' => $user->subscription_plan,
        ]);
    }

    /**
     * Mark subscription as active (after payment confirmed)
     */
    public function activateSubscription(User $user): void
    {
        $user->update([
            'subscription_status' => 'active',
            'subscription_started_at' => now(),
        ]);

        $user->subscriptionEvents()->create([
            'event_type' => 'renewed',
            'plan' => $user->subscription_plan,
        ]);
    }
}
