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

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'pawapay' => [
        'sandbox' => env('PAWAPAY_SANDBOX', true),
        'base_url' => env('PAWAPAY_BASE_URL', 'https://api.sandbox.pawapay.io'),
        'api_key' => env('PAWAPAY_API_KEY'),
        'webhook_base_url' => rtrim(env('APP_URL', 'http://localhost'), '/'),
    ],

    // SafeHaven bank integration
    'safehaven' => [
        'webhook_secret' => env('SAFEHAVEN_WEBHOOK_SECRET'),
        'webhook_base_url' => rtrim(env('SAFEHAVEN_WEBHOOK_BASE_URL', env('APP_URL', 'http://localhost')), '/'),
    ],

    // Optional: Paystack for card funding (phase 2)
    'paystack' => [
        'secret' => env('PAYSTACK_SECRET'),
        'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'phone_number' => env('TWILIO_PHONE_NUMBER'),
    ],

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
        'project_id' => env('FCM_PROJECT_ID'),
        'service_account_path' => env('FCM_SERVICE_ACCOUNT_PATH'),
    ],

    // Screening provider configuration
    'screening' => [
        // driver: internal|smileid (extendable)
        'driver' => env('SCREENING_DRIVER', 'internal'),
    ],
];
