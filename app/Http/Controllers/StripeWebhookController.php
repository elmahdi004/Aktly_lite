<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Subscription;
use App\Models\User;
use App\Services\StripeSubscriptionService;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    protected StripeSubscriptionService $subscriptionService;

    public function __construct(StripeSubscriptionService $subscriptionService)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * POST /stripe/webhook
     * Handle Stripe webhook events
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = env('STRIPE_WEBHOOK_SECRET');

        Log::info('Stripe webhook request received', [
            'has_signature' => !empty($signature),
            'secret_set' => !empty($secret),
            'payload_size' => strlen($payload),
        ]);

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid webhook payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Invalid webhook signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'invalid signature'], 400);
        }

        Log::info('Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id,
        ]);

        try {
            match ($event->type) {
                'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event),
                'customer.subscription.created' => $this->handleSubscriptionCreated($event),
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
                'invoice.paid' => $this->handleInvoicePaid($event),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event),
                default => null,
            };
        } catch (\Exception $e) {
            Log::error('Webhook handling failed', [
                'event_type' => $event->type,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Handle subscription.created event
     * This fires after successful payment
     */
    private function handleSubscriptionCreated($event): void
    {
        $subscription = $event->data->object;

        Log::info('Handling subscription.created', [
            'subscription_id' => $subscription->id ?? null,
            'customer_id' => $subscription->customer ?? null,
            'status' => $subscription->status ?? null,
        ]);

        $user = User::where('stripe_customer_id', $subscription->customer)->first();

        if (!$user) {
            Log::warning('User not found for subscription', ['customer_id' => $subscription->customer]);
            return;
        }

        $user->update([
            'stripe_subscription_id' => $subscription->id,
            'subscription_status' => $subscription->status,
            'subscription_started_at' => now(),
        ]);

        $user->subscriptionEvents()->create([
            'event_type' => 'created',
            'plan' => $user->subscription_plan,
            'stripe_event_id' => $event->id,
            'payload' => (array) $subscription,
        ]);

        Log::info('Subscription created', ['user_id' => $user->id, 'subscription_id' => $subscription->id]);
    }

    /**
     * Handle checkout.session.completed event
     * Useful for debugging and linking subscription/customer to user
     */
    private function handleCheckoutSessionCompleted($event): void
    {
        $session = $event->data->object;

        // Use ->toArray() so Stripe SDK StripeObject metadata gives a plain associative
        // array instead of a private-property dump from (array) casting.
        $metadata = ($session->metadata instanceof \Stripe\StripeObject)
            ? $session->metadata->toArray()
            : (array) ($session->metadata ?? []);
        $userId = $metadata['user_id'] ?? null;

        Log::info('Handling checkout.session.completed', [
            'session_id' => $session->id ?? null,
            'customer_id' => $session->customer ?? null,
            'subscription_id' => $session->subscription ?? null,
            'payment_status' => $session->payment_status ?? null,
            'metadata' => $metadata,
        ]);

        if (!$userId) {
            Log::warning('Checkout session missing user_id metadata', [
                'session_id' => $session->id ?? null,
            ]);
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            Log::warning('User not found for checkout session', [
                'user_id' => $userId,
                'session_id' => $session->id ?? null,
            ]);
            return;
        }

        $update = [];
        if (!empty($session->customer)) {
            $update['stripe_customer_id'] = $session->customer;
        }
        if (!empty($session->subscription)) {
            $update['stripe_subscription_id'] = $session->subscription;
        }
        if (!empty($update)) {
            $user->update($update);
        }

        // If this checkout was a one-time product (one_dossier), grant credits
        $product = $metadata['product'] ?? null;

        Log::info('[webhook] checkout product detected', [
            'product'        => $product,
            'user_id'        => $user->id,
            'payment_status' => $session->payment_status ?? null,
            'session_id'     => $session->id ?? null,
        ]);

        if ($product === 'one_dossier') {
            // Only process grants when payment completed
            if (($session->payment_status ?? null) === 'paid') {
                $sessionId = $session->id ?? null;

                $alreadyProcessed = $user->subscriptionEvents()
                    ->where('event_type', 'one_time_purchase')
                    ->where(function ($query) use ($event, $sessionId) {
                        $query->where('stripe_event_id', $event->id);

                        if ($sessionId) {
                            $query->orWhere('payload->session_id', $sessionId)
                                ->orWhere('payload->id', $sessionId);
                        }
                    })
                    ->exists();

                Log::info('[webhook] one_dossier already-processed check', [
                    'user_id'          => $user->id,
                    'session_id'       => $sessionId,
                    'stripe_event_id'  => $event->id,
                    'already_processed' => $alreadyProcessed,
                ]);

                if ($alreadyProcessed) {
                    Log::info('[webhook] One-dossier already processed, skipping', [
                        'user_id'        => $user->id,
                        'stripe_event_id' => $event->id,
                        'session_id'     => $sessionId,
                    ]);
                    return;
                }

                $config = config('subscriptions.one_time.one_dossier');
                $creditsToGrant = $config['credits'] ?? 5;
                $expiresMonths  = $config['expires_months'] ?? 12;

                Log::info('[webhook] Granting one-dossier credits', [
                    'user_id'        => $user->id,
                    'credits_before' => $user->credits,
                    'credits_grant'  => $creditsToGrant,
                    'expires_months' => $expiresMonths,
                ]);

                // Increment credits and set expiry
                try {
                    $user->increment('credits', $creditsToGrant);
                    $user->update(['credits_expires_at' => now()->addMonths($expiresMonths)]);

                    $user->subscriptionEvents()->create([
                        'event_type'     => 'one_time_purchase',
                        'plan'           => 'one_dossier',
                        'stripe_event_id' => $event->id,
                        'payload'        => [
                            'session_id' => $sessionId,
                            'source'     => 'webhook',
                            'session'    => (array) $session,
                        ],
                    ]);

                    Log::info('[webhook] Credits granted successfully', [
                        'user_id'       => $user->id,
                        'credits_after' => $user->fresh()->credits,
                        'expires_at'    => $user->fresh()->credits_expires_at,
                    ]);
                } catch (\Exception $e) {
                    Log::error('[webhook] Failed to grant one-dossier credits', [
                        'user_id' => $user->id,
                        'error'   => $e->getMessage(),
                        'trace'   => $e->getTraceAsString(),
                    ]);
                }
            } else {
                Log::warning('[webhook] one_dossier session payment_status not paid', [
                    'user_id'        => $user->id,
                    'payment_status' => $session->payment_status ?? null,
                    'session_id'     => $session->id ?? null,
                ]);
            }
            return;
        }

        if (($session->payment_status ?? null) === 'paid') {
            $user->update([
                'subscription_status' => 'active',
                'subscription_started_at' => now(),
            ]);
        }
    }

    /**
     * Handle subscription.updated event
     * Triggered on plan changes or status changes
     */
    private function handleSubscriptionUpdated($event): void
    {
        $subscription = $event->data->object;

        $user = User::where('stripe_subscription_id', $subscription->id)->first();

        if (!$user) {
            Log::warning('User not found for subscription update', ['subscription_id' => $subscription->id]);
            return;
        }

        $previousStatus = $user->subscription_status;
        $newStatus = $subscription->status;
        $previousAttributes = (array) ($event->data->previous_attributes ?? []);

        $user->update([
            'subscription_status' => $newStatus,
            'subscription_plan' => $subscription->metadata->plan ?? $user->subscription_plan,
            'subscription_ends_at' => $subscription->cancel_at ? now()->setTimestamp($subscription->cancel_at) : null,
        ]);

        // Log status change
        if ($previousStatus !== $newStatus) {
            $eventType = match ($newStatus) {
                'active' => 'renewed',
                'past_due' => 'failed',
                'canceled' => 'canceled',
                default => 'updated',
            };

            $user->subscriptionEvents()->create([
                'event_type' => $eventType,
                'plan' => $user->subscription_plan,
                'stripe_event_id' => $event->id,
                'payload' => (array) $subscription,
            ]);
        }

        if ($this->shouldResetOnPlanChange($previousAttributes)) {
            $this->resetCumulativeDossierCount($user, 'plan_change');
        }

        Log::info('Subscription updated', [
            'user_id' => $user->id,
            'status' => $newStatus,
        ]);
    }

    /**
     * Handle subscription.deleted event
     */
    private function handleSubscriptionDeleted($event): void
    {
        $subscription = $event->data->object;

        $user = User::where('stripe_subscription_id', $subscription->id)->first();

        if (!$user) {
            Log::warning('User not found for subscription deletion', ['subscription_id' => $subscription->id]);
            return;
        }

        $user->update([
            'subscription_status' => 'canceled',
            'subscription_ends_at' => now(),
        ]);

        $user->subscriptionEvents()->create([
            'event_type' => 'canceled',
            'plan' => $user->subscription_plan,
            'stripe_event_id' => $event->id,
        ]);

        Log::info('Subscription canceled', ['user_id' => $user->id]);
    }

    /**
     * Handle invoice.paid event
     * Credits have been charged
     */
    private function handleInvoicePaid($event): void
    {
        $invoice = $event->data->object;

        Log::info('Handling invoice.paid', [
            'invoice_id' => $invoice->id ?? null,
            'customer_id' => $invoice->customer ?? null,
            'amount_paid' => $invoice->amount_paid ?? null,
        ]);

        $user = User::where('stripe_customer_id', $invoice->customer)->first();

        if (!$user) {
            Log::warning('User not found for invoice', ['customer_id' => $invoice->customer]);
            return;
        }

        // Activate subscription if not already active
        if ($user->subscription_status !== 'active') {
            $this->subscriptionService->activateSubscription($user);
        }

        if ($this->shouldResetOnInvoicePaid($invoice)) {
            $this->resetCumulativeDossierCount($user, 'billing_cycle');
        }

        Log::info('Invoice paid', [
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_paid,
        ]);
    }

    /**
     * Handle invoice.payment_failed event
     * Payment could not be processed
     */
    private function handleInvoicePaymentFailed($event): void
    {
        $invoice = $event->data->object;

        $user = User::where('stripe_customer_id', $invoice->customer)->first();

        if (!$user) {
            Log::warning('User not found for failed invoice', ['customer_id' => $invoice->customer]);
            return;
        }

        $user->update([
            'subscription_status' => 'past_due',
        ]);

        $user->subscriptionEvents()->create([
            'event_type' => 'failed',
            'plan' => $user->subscription_plan,
            'stripe_event_id' => $event->id,
        ]);

        Log::info('Invoice payment failed', [
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);
    }

    private function shouldResetOnPlanChange(array $previousAttributes): bool
    {
        return array_key_exists('items', $previousAttributes)
            || array_key_exists('metadata', $previousAttributes)
            || array_key_exists('plan', $previousAttributes);
    }

    private function shouldResetOnInvoicePaid($invoice): bool
    {
        $billingReason = $invoice->billing_reason ?? null;

        return in_array($billingReason, ['subscription_cycle', 'subscription_update', 'subscription_create'], true);
    }

    private function resetCumulativeDossierCount(User $user, string $reason): void
    {
        if ($user->cumulative_dossier_count === 0) {
            return;
        }

        $user->update([
            'cumulative_dossier_count' => 0,
        ]);

        Log::info('Reset cumulative dossier count', [
            'user_id' => $user->id,
            'reason' => $reason,
        ]);
    }
}
