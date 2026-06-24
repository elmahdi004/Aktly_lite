<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StripeSubscriptionService;
use App\Services\CouponValidationService;
use App\Services\StripeOneTimeService;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Carbon\Carbon;

class BillingController extends Controller
{
    protected StripeSubscriptionService $subscriptionService;
    protected CouponValidationService $couponService;
    protected StripeOneTimeService $oneTimeService;

    public function __construct(
        StripeSubscriptionService $subscriptionService,
        CouponValidationService $couponService,
        StripeOneTimeService $oneTimeService
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->couponService = $couponService;
        $this->oneTimeService = $oneTimeService;
    }

    /**
     * GET /billing/dossier-price
     * Return the current one-shot dossier price (read from Stripe).
     */
    public function dossierPrice()
    {
        // Configured fallback amount (shown when Stripe can't be queried, e.g. the Lite
        // price lives in a different Stripe account than the configured secret key).
        $fallback = (float) config('subscriptions.one_time.one_dossier.price', 0);

        try {
            $priceId = env('STRIPE_LITE_PRICE_ID')
                ?: config('subscriptions.one_time.one_dossier.stripe_price_id_fr');
            if (!$priceId) {
                return response()->json(['amount' => $fallback ?: null, 'currency' => 'EUR']);
            }
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            $price = $stripe->prices->retrieve($priceId);
            return response()->json([
                'amount' => $price->unit_amount !== null ? $price->unit_amount / 100 : ($fallback ?: null),
                'currency' => strtoupper($price->currency ?? 'eur'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('dossierPrice fetch failed', ['error' => $e->getMessage()]);
            return response()->json(['amount' => $fallback ?: null, 'currency' => 'EUR']);
        }
    }

    /**
     * POST /billing/subscribe
     * Create a new subscription for the user
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'plan' => 'required|string|in:starter,pro,business',
            'coupon_code' => 'nullable|string',
            'locale' => 'nullable|string|in:fr,nl',
        ]);

        try {
            $plan = $validated['plan'];
            $couponCode = $validated['coupon_code'] ?? null;
            $locale = $validated['locale'] ?? $request->user()->langue_user ?? 'fr';

            // If user already has an active subscription
            if ($request->user()->stripe_subscription_id && $request->user()->subscription_status === 'active') {
                // If switching plans, upgrade/downgrade instead
                if ($request->user()->subscription_plan !== $plan) {
                    $this->subscriptionService->updateSubscription(
                        $request->user(),
                        $plan,
                        $locale
                    );

                    return response()->json([
                        'message' => 'Subscription updated',
                        'plan' => $plan,
                    ]);
                }

                return response()->json([
                    'message' => 'Already on this plan',
                ], 422);
            }

            // Create new subscription (redirects to Checkout)
            $result = $this->subscriptionService->createSubscription(
                $request->user(),
                $plan,
                $couponCode,
                $locale
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Subscription creation failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /billing/checkout/success
     * Handle successful Stripe Checkout
     */
    public function checkoutSuccess(Request $request)
    {
        $sessionId = $request->query('session_id');
        if (!$sessionId) {
            return response()->json(['error' => 'No session ID provided'], 400);
        }

        try {
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            $session = $stripe->checkout->sessions->retrieve($sessionId);

            // Use ->toArray() so Stripe SDK StripeObject metadata gives a plain associative
            // array instead of a private-property dump from (array) casting.
            $metadata = ($session->metadata instanceof \Stripe\StripeObject)
                ? $session->metadata->toArray()
                : (array) ($session->metadata ?? []);
            $product = $metadata['product'] ?? null;

            Log::info('[checkoutSuccess] Session retrieved', [
                'session_id'     => $sessionId,
                'payment_status' => $session->payment_status ?? null,
                'mode'           => $session->mode ?? null,
                'product'        => $product,
                'customer'       => $session->customer ?? null,
                'metadata'       => $metadata,
            ]);

            // Handle one-time product checkout (Pack 1 Dossier) as a fallback when
            // webhook processing is delayed/unavailable.
            if ($product === 'one_dossier') {
                Log::info('[checkoutSuccess] one_dossier flow entered', ['session_id' => $sessionId]);

                if (($session->payment_status ?? null) !== 'paid') {
                    Log::warning('[checkoutSuccess] Payment not completed', [
                        'session_id'     => $sessionId,
                        'payment_status' => $session->payment_status ?? null,
                    ]);
                    return response()->json(['error' => 'Payment not completed'], 422);
                }

                $user = null;
                $metadataUserId = $metadata['user_id'] ?? null;

                Log::info('[checkoutSuccess] Looking up user', [
                    'metadata_user_id' => $metadataUserId,
                    'stripe_customer'  => $session->customer ?? null,
                ]);

                if ($metadataUserId) {
                    $user = \App\Models\User::find($metadataUserId);
                    Log::info('[checkoutSuccess] User lookup by metadata_user_id', [
                        'found' => $user ? true : false,
                        'user_id' => $metadataUserId,
                    ]);
                }

                if (!$user && !empty($session->customer)) {
                    $user = \App\Models\User::where('stripe_customer_id', $session->customer)->first();
                    Log::info('[checkoutSuccess] User lookup by stripe_customer_id', [
                        'found'           => $user ? true : false,
                        'stripe_customer' => $session->customer,
                    ]);
                }

                if (!$user) {
                    Log::error('[checkoutSuccess] User not found for session', ['session_id' => $sessionId]);
                    return response()->json(['error' => 'User not found for session'], 404);
                }

                Log::info('[checkoutSuccess] User found', [
                    'user_id'            => $user->id,
                    'credits_before'     => $user->credits,
                    'credits_expires_at' => $user->credits_expires_at,
                ]);

                if (!empty($session->customer) && $user->stripe_customer_id !== $session->customer) {
                    $user->update(['stripe_customer_id' => $session->customer]);
                    Log::info('[checkoutSuccess] stripe_customer_id updated on user', ['user_id' => $user->id]);
                }

                $alreadyProcessed = $user->subscriptionEvents()
                    ->where('event_type', 'one_time_purchase')
                    ->where(function ($query) use ($sessionId) {
                        $query->where('payload->session_id', $sessionId)
                            ->orWhere('payload->id', $sessionId);
                    })
                    ->exists();

                Log::info('[checkoutSuccess] Already-processed check', [
                    'session_id'       => $sessionId,
                    'already_processed' => $alreadyProcessed,
                ]);

                if (!$alreadyProcessed) {
                    $config = config('subscriptions.one_time.one_dossier');
                    $creditsToGrant = $config['credits'] ?? 5;
                    $expiresMonths  = $config['expires_months'] ?? 12;

                    Log::info('[checkoutSuccess] Granting credits', [
                        'user_id'       => $user->id,
                        'credits_grant' => $creditsToGrant,
                        'expires_months' => $expiresMonths,
                    ]);

                    $user->increment('credits', $creditsToGrant);
                    $user->update(['credits_expires_at' => now()->addMonths($expiresMonths)]);

                    $user->subscriptionEvents()->create([
                        'event_type' => 'one_time_purchase',
                        'plan'       => 'one_dossier',
                        'payload'    => [
                            'session_id' => $sessionId,
                            'source'     => 'checkout_success',
                            'customer'   => $session->customer ?? null,
                        ],
                    ]);

                    Log::info('[checkoutSuccess] Credits granted', [
                        'user_id'        => $user->id,
                        'credits_after'  => $user->fresh()->credits,
                        'expires_at'     => $user->fresh()->credits_expires_at,
                    ]);
                } else {
                    Log::info('[checkoutSuccess] Skipping grant – already processed', [
                        'user_id'    => $user->id,
                        'session_id' => $sessionId,
                    ]);
                }

                return response()->json([
                    'message' => 'One-time purchase successful',
                    'credits' => $user->fresh()->credits,
                    'redirect' => '/dashboard',
                ]);
            }

            if ($session->customer) {
                $user = \App\Models\User::where('stripe_customer_id', $session->customer)->first();
                if ($user && isset($metadata['plan'])) {
                    // Update user with active subscription
                    $user->update([
                        'subscription_status' => 'active',
                    ]);

                    // If coupon was used, record it
                    if (!empty($metadata['coupon_code'])) {
                        $this->couponService->recordDossierUsage(
                            $metadata['coupon_code'],
                            $user->id,
                            0
                        );
                    }

                    // Log event
                    $user->subscriptionEvents()->create([
                        'event_type' => 'checkout_completed',
                        'plan' => $metadata['plan'],
                        'payload' => [
                            'session_id' => $sessionId,
                        ],
                    ]);
                }
            }

            return response()->json(['message' => 'Subscription successful', 'redirect' => '/dashboard']);
        } catch (\Exception $e) {
            Log::error('Checkout success failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Checkout verification failed'], 422);
        }
    }

    /**
     * POST /billing/validate-coupon
     * Validate a coupon code
     */
    public function validateCoupon(Request $request)
    {
        $validated = $request->validate([
            'coupon_code' => 'required|string',
        ]);

        $result = $this->couponService->validate(
            $validated['coupon_code'],
            $request->user()->id
        );

        if (!$result['valid']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * GET /billing/subscription
     * Get current subscription status
     */
    public function getSubscription(Request $request)
    {
        $user = $request->user();

        $response = [
            'active' => $user->stripe_subscription_id ? $user->isSubscriptionActive() : false,
            'plan' => $user->subscription_plan,
            'status' => $user->subscription_status,
            'started_at' => $user->subscription_started_at,
            'ends_at' => $user->subscription_ends_at,
            'next_billing_at' => null,
            'dossiers_created' => $user->cumulative_dossier_count,
            'dossiers_remaining' => $user->getDossiersRemaining(),
            'plan_quota' => $user->getPlanQuota(),
            'extra_dossier_price' => $user->getExtraDossierPrice(),
            'applied_coupon_code' => $user->applied_coupon_code,
            // one-time credits
            'credits' => $user->credits ?? 0,
            'credits_expires_at' => $user->credits_expires_at,
        ];

        if ($user->stripe_subscription_id) {
            try {
                $stripe = new StripeClient(env('STRIPE_SECRET'));
                $subscription = $stripe->subscriptions->retrieve($user->stripe_subscription_id);
                if (!empty($subscription->current_period_end)) {
                    $response['next_billing_at'] = Carbon::createFromTimestamp($subscription->current_period_end)->toISOString();
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch Stripe subscription billing date', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Add coupon details if coupon is applied
        if ($user->applied_coupon_code) {
            $coupon = \App\Models\Coupon::where('code', $user->applied_coupon_code)->first();
            if ($coupon) {
                // Get current usage by coupon_id
                $usage = \App\Models\CouponUsage::where('coupon_id', $coupon->id)
                    ->where('user_id', $user->id)
                    ->first();
                
                $dossiers_used = $usage ? $usage->dossiers_used : 0;
                $dossiers_remaining = max(0, $coupon->max_dossiers - $dossiers_used);
                
                $response['coupon_description'] = $coupon->description;
                $response['coupon_max_dossiers'] = $coupon->max_dossiers;
                $response['coupon_dossiers_used'] = $dossiers_used;
                $response['coupon_dossiers_remaining'] = $dossiers_remaining;
                $response['coupon_expires_at'] = $coupon->expires_at;
            }
        }

        return response()->json($response);
    }

    /**
     * POST /billing/purchase-one-dossier
     * Create a one-time checkout session for the 1-dossier pack
     */
    public function purchaseOneDossier(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'nullable|string|in:fr,nl',
        ]);

        $locale = $validated['locale'] ?? $request->user()->langue_user ?? 'fr';

        try {
            $result = $this->oneTimeService->createOneDossierSession($request->user(), $locale);
            return response()->json($result);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('One-dossier purchase failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback: redirect to the hosted Stripe Payment Link (works even when the
            // dynamic Checkout Session can't be created — e.g. the price lives in a Stripe
            // account whose secret key isn't configured here). client_reference_id lets a
            // webhook reconcile the payment to this user later.
            $link = env('STRIPE_LITE_PAYMENT_LINK');
            if ($link) {
                $sep = str_contains($link, '?') ? '&' : '?';
                return response()->json([
                    'checkout_url' => $link . $sep . 'client_reference_id=' . $request->user()->id,
                    'via' => 'payment_link',
                ]);
            }

            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /billing/cancel
     * Cancel subscription
     */
    public function cancelSubscription(Request $request)
    {
        $validated = $request->validate([
            'immediate' => 'nullable|boolean',
        ]);

        try {
            $this->subscriptionService->cancelSubscription(
                $request->user(),
                $validated['immediate'] ?? false
            );

            return response()->json(['message' => 'Subscription cancelled']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /billing/apply-coupon
     * Apply coupon to user account
     */
    public function applyCoupon(Request $request)
    {
        $validated = $request->validate([
            'coupon_code' => 'required|string|max:50',
        ]);

        $result = $this->couponService->applyCoupon(
            $request->user(),
            strtoupper($validated['coupon_code'])
        );

        if (!$result['applied']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * POST /billing/remove-coupon
     * Remove coupon from user account
     */
    public function removeCoupon(Request $request)
    {
        $user = $request->user();

        if (!$user->applied_coupon_code) {
            return response()->json(['message' => 'No coupon applied'], 404);
        }

        $this->couponService->removeCouponFromUser($user, 'user_requested');

        return response()->json(['message' => 'Coupon removed successfully']);
    }
}
