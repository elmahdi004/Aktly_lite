<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Customer;
use App\Models\User;

class StripeCustomerService
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    /**
     * Get or create a Stripe customer for a user
     */
    public function getOrCreateCustomer(User $user): Customer
    {
        if ($user->stripe_customer_id) {
            return Customer::retrieve($user->stripe_customer_id);
        }

        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
                'created_at' => now()->toIso8601String(),
            ],
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    /**
     * Update customer metadata
     */
    public function updateCustomerMetadata(User $user, array $metadata): void
    {
        if (!$user->stripe_customer_id) {
            $this->getOrCreateCustomer($user);
        }

        Customer::update($user->stripe_customer_id, [
            'metadata' => $metadata,
        ]);
    }
}
