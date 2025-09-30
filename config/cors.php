<?php

return [
    'paths' => ['api/*'],

    // Allow credentials for session cookies across origins
    'supports_credentials' => true,

    // Configure allowed origins via env; separate multiple with commas
    'allowed_origins' => array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', env('MOBILE_APP_ORIGIN', '')))),

    // Patterns (unused when allowed_origins is set explicitly)
    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Accept',
        'Authorization',
        'Idempotency-Key',
    ],

    'allowed_methods' => ['GET','POST','PUT','PATCH','DELETE','OPTIONS'],

    'exposed_headers' => [
        'Set-Cookie',
    ],

    'max_age' => 3600,
];
