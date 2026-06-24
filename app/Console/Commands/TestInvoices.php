<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Stripe\Stripe;

class TestInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:invoices {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test invoice retrieval for a user by email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }

        $this->info("User: {$user->name} ({$user->email})");
        $this->info("Stripe Customer ID: " . ($user->stripe_customer_id ?? 'NOT SET'));
        $this->info("Credits: {$user->credits}");

        if (!$user->stripe_customer_id) {
            $this->warn("No Stripe customer ID set for this user.");
            return 0;
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            // Fetch all invoices
            $this->info("\n=== FETCHING ALL INVOICES ===");
            $allInvoices = \Stripe\Invoice::all([
                'customer' => $user->stripe_customer_id,
                'limit'    => 100,
            ]);
            
            $this->info("Total invoices found: " . count($allInvoices->data));
            
            foreach ($allInvoices->data as $inv) {
                $this->line("  - ID: {$inv->id}");
                $this->line("    Number: " . ($inv->number ?? 'N/A'));
                $this->line("    Status: {$inv->status}");
                $this->line("    Amount: " . ($inv->amount_total / 100) . " {$inv->currency}");
                $this->line("    PDF: " . ($inv->invoice_pdf ?? 'NO PDF'));
                $this->line("");
            }

            // Fetch payment intents
            $this->info("\n=== FETCHING PAYMENT INTENTS ===");
            $payments = \Stripe\PaymentIntent::all([
                'customer' => $user->stripe_customer_id,
                'limit'    => 100,
            ]);

            $this->info("Total payment intents found: " . count($payments->data));
            
            foreach ($payments->data as $pi) {
                $this->line("  - ID: {$pi->id}");
                $this->line("    Status: {$pi->status}");
                $this->line("    Amount: " . ($pi->amount / 100) . " {$pi->currency}");
                if (count($pi->charges->data) > 0) {
                    $charge = $pi->charges->data[0];
                    $this->line("    Receipt URL: " . ($charge->receipt_url ?? 'NO RECEIPT'));
                }
                $this->line("");
            }

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
