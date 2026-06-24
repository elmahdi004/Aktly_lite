<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\User;
use App\Services\StripeCustomerService;

class StripeOneTimeService
{
    protected StripeCustomerService $customerService;

    public function __construct(StripeCustomerService $customerService)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $this->customerService = $customerService;
    }

    /**
     * Create a one-time Checkout Session for the one-dossier pack
     * @param User $user
     * @param string $locale fr|nl
     * @return array
     */
    public function createOneDossierSession(User $user, string $locale = 'fr'): array
    {
        $config = config('subscriptions.one_time.one_dossier');
        if (!$config) {
            throw new \Exception('One-dossier configuration missing');
        }

        $priceKey = 'stripe_price_id_' . $locale;
        $priceId = $config[$priceKey] ?? null;
        if (!$priceId) {
            throw new \Exception("Missing Stripe price ID for one_dossier locale $locale");
        }

        // Ensure we have a Stripe customer so customer_update is valid
        $customer = $this->customerService->getOrCreateCustomer($user);

        $sessionParams = [
            'customer' => $customer->id,
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => url('/buy-credits/success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => url('/buy-credits/cancel'),
            'billing_address_collection' => 'required',
            'tax_id_collection' => [
                'enabled' => true,
            ],
            'invoice_creation' => [
                'enabled' => true,
                'invoice_data' => [
                    'metadata' => [
                        'user_id' => (string) $user->id,
                        'product' => 'one_dossier',
                    ],
                ],
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'user_id' => $user->id,
                    'product' => 'one_dossier',
                ],
            ],
            'metadata' => [
                'user_id' => $user->id,
                'product' => 'one_dossier',
            ],
            'customer_update' => [
                'address' => 'auto',
                'name' => 'auto',
            ],
        ];

        $session = Session::create($sessionParams);

        return [
            'session_id' => $session->id,
            'checkout_url' => $session->url,
        ];
    }
}
