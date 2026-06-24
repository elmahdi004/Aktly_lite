<?php

return [
    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'monthly_price' => 69.00,
            'included_dossiers' => 3,
            'extra_dossier_price' => 19.00,
            'stripe_price_id_fr' => env('STRIPE_STARTER_PRICE_ID_FR'),
            'stripe_price_id_nl' => env('STRIPE_STARTER_PRICE_ID_NL'),
            'stripe_usage_price_id_fr' => env('STRIPE_STARTER_USAGE_PRICE_ID_FR'),
            'stripe_usage_price_id_nl' => env('STRIPE_STARTER_USAGE_PRICE_ID_NL'),
        ],
        'pro' => [
            'name' => 'Pro',
            'monthly_price' => 99.00,
            'included_dossiers' => 5,
            'extra_dossier_price' => 15.00,
            'stripe_price_id_fr' => env('STRIPE_PRO_PRICE_ID_FR'),
            'stripe_price_id_nl' => env('STRIPE_PRO_PRICE_ID_NL'),
            'stripe_usage_price_id_fr' => env('STRIPE_PRO_USAGE_PRICE_ID_FR'),
            'stripe_usage_price_id_nl' => env('STRIPE_PRO_USAGE_PRICE_ID_NL'),
        ],
        'business' => [
            'name' => 'Business',
            'monthly_price' => 169.00,
            'included_dossiers' => 10,
            'extra_dossier_price' => 10.00,
            'stripe_price_id_fr' => env('STRIPE_BUSINESS_PRICE_ID_FR'),
            'stripe_price_id_nl' => env('STRIPE_BUSINESS_PRICE_ID_NL'),
            'stripe_usage_price_id_fr' => env('STRIPE_BUSINESS_USAGE_PRICE_ID_FR'),
            'stripe_usage_price_id_nl' => env('STRIPE_BUSINESS_USAGE_PRICE_ID_NL'),
        ],
    ],
    'one_time' => [
        'one_dossier' => [
            'name' => 'Pack 1 Dossier',
            'price' => 39.00,
            'credits' => 1,
            'expires_months' => 12,
            // Aktly Lite: one-shot price per dossier (falls back to the legacy one-dossier price).
            'stripe_price_id_fr' => env('STRIPE_LITE_PRICE_ID', env('STRIPE_ONE_DOSSIER_PRICE_ID_FR')),
            'stripe_price_id_nl' => env('STRIPE_LITE_PRICE_ID', env('STRIPE_ONE_DOSSIER_PRICE_ID_NL')),
        ],
    ],
];
