<?php

namespace App\Services;

use Stripe\Stripe;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class StripeUsageService
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    /**
     * Charge extra dossiers immediately
     * Called when dossier count exceeds plan quota
     */
    public function reportExtraDossier(User $user, int $extraCount = 1): void
    {
        if (!$user->stripe_subscription_id) {
            Log::warning('User has no subscription for immediate billing', ['user_id' => $user->id]);
            return;
        }

        try {
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            
            // Get price from user's plan
            $extraPrice = $user->getExtraDossierPrice();
            $amount = $extraPrice * 100 * $extraCount; // Convert to cents

            // Retrieve subscription to get payment method
            $subscription = $stripe->subscriptions->retrieve($user->stripe_subscription_id);
            $paymentMethod = $subscription->default_payment_method;

            if (!$paymentMethod) {
                Log::error('No payment method found on subscription', [
                    'user_id' => $user->id,
                    'subscription_id' => $user->stripe_subscription_id,
                ]);
                return;
            }

            // Create invoice draft first
            $invoice = $stripe->invoices->create([
                'customer' => $user->stripe_customer_id,
                'auto_advance' => false,
                'collection_method' => 'charge_automatically',
            ]);

            // Attach the invoice item directly to this invoice
            $stripe->invoiceItems->create([
                'customer' => $user->stripe_customer_id,
                'invoice' => $invoice->id,
                'amount' => $amount,
                'currency' => 'eur',
                'description' => sprintf('Dossier supplémentaire ×%d', $extraCount),
            ]);

            $finalizedInvoice = $stripe->invoices->finalizeInvoice($invoice->id);
            $paidInvoice = $finalizedInvoice;

            if ($finalizedInvoice->status !== 'paid' && $finalizedInvoice->amount_due > 0) {
                $paidInvoice = $stripe->invoices->pay($finalizedInvoice->id, [
                    'payment_method' => $paymentMethod,
                ]);
            }

            Log::info('Immediate invoice created for extra dossiers', [
                'user_id' => $user->id,
                'invoice_id' => $paidInvoice->id,
                'charge_id' => $paidInvoice->charge,
                'amount' => $amount / 100,
                'extra_dossiers' => $extraCount,
                'status' => $paidInvoice->status,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create immediate payment for extra dossiers', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't throw—let the dossier creation complete even if billing fails
        }
    }
}
