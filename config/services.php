<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
        'assistant_id' => env('OPENAI_ASSISTANT_ID'),
    ],
    'openai_convert' => [
        'urls' => array_filter(array_map('trim', explode(',', (string) env('OPENAI_CONVERT_URLS', '')))),
    ],
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-pro'),
    ],
    'claude' => [
        'api_key' => env('CLAUDE_API_KEY'),
        'model' => env('CLAUDE_MODEL', 'claude-3-5-sonnet-latest'),
    ],
    'bnb' => [
    'base_url' => env('BNB_API_BASE_URL'),
    'base_url_dev' => env('BNB_API_BASE_URL_DEV'),
    'api_key' => env('BNB_API_KEY'),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
    ],

    'id_analyzer' => [
        'api_key' => env('ID_ANALYZER_API_KEY'),
        'api_url' => env('ID_ANALYZER_API_URL', 'https://api2.idanalyzer.com'),
        'secret_key' => env('ID_ANALYZER_SECRET_KEY'),
        'session_endpoint' => env('ID_ANALYZER_SESSION_ENDPOINT', 'docupass'),
        'profile_id' => env('ID_ANALYZER_PROFILE_ID'),
    ],


];
