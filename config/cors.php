<?php

return [
    // Include Sanctum CSRF cookie endpoint and your API
    'paths' => ['sanctum/csrf-cookie', 'api/*'],

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
        'X-XSRF-TOKEN', // needed for Sanctum
    ],

    'allowed_methods' => ['GET','POST','PUT','PATCH','DELETE','OPTIONS'],

    // Exposing Set-Cookie is optional; cookies aren’t readable via JS anyway
    'exposed_headers' => [
        // 'Set-Cookie',
    ],

    'max_age' => 3600,
];