<?php

namespace App\Http\Controllers;

// use Illuminate\Container\Attributes\Log;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Stripe\Stripe;

class InvoiceController extends Controller
{
    /**
     * GET /api/invoices
     * Auth: Sanctum token
     * Response: array of Stripe invoices (latest first)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->stripe_customer_id) {
            return response()->json([]);        // never bought anything yet
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $invoices = \Stripe\Invoice::all([
                'customer' => $user->stripe_customer_id,
                'limit'    => 100,
                // 'status'   => 'paid',           // Only show paid invoices
            ]);

            // return only useful fields, filter out null values
            $data = collect($invoices->data)
                ->filter(fn ($inv) => $inv->status === 'paid' && $inv->invoice_pdf )
                ->map(fn ($inv) => [
                    'id'            => $inv->id,
                    'number'        => $inv->number ?? 'N/A',
                    'amount_total'  => $inv->total / 100,   // Use 'total' not 'amount_total'
                    'currency'      => strtoupper($inv->currency),
                    'status'        => $inv->status,
                    'pdf'           => $inv->invoice_pdf,
                    'created_at'    => $inv->created,
                ])
                ->values();

            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error('Error fetching invoices', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Unable to fetch invoices'], 500);
        }
    }

    public function receipts(Request $request)
    {
        $user = $request->user();
        if (!$user->stripe_customer_id) {
            return response()->json([]);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $payments = \Stripe\PaymentIntent::all([
                'customer' => $user->stripe_customer_id,
                'limit'    => 100,
            ]);

            $data = collect($payments->data)->flatMap(function ($pi) {
                return collect($pi->charges->data)
                    ->filter(fn ($ch) => $ch->paid && $ch->receipt_url)
                    ->map(fn ($ch) => [
                        'id'          => $pi->id,
                        'amount'      => $ch->amount / 100,
                        'currency'    => strtoupper($ch->currency),
                        'status'      => $ch->status,
                        'created_at'  => $ch->created,
                        'receipt_url' => $ch->receipt_url,
                    ]);
            })->values();

            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error('Error fetching receipts', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Unable to fetch receipts'], 500);
        }
    }
}
