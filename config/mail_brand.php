<?php
return [
    'brand_name' => env('MAIL_BRAND_NAME', 'TexaPay'),
    'logo_url' => env('MAIL_BRAND_LOGO', ''),
    'primary' => env('MAIL_BRAND_PRIMARY', '#0f172a'),
    'accent' => env('MAIL_BRAND_ACCENT', '#0ea5e9'),
    'footer_bg' => env('MAIL_BRAND_FOOTER_BG', '#1f2937'),
    'footer_text' => env('MAIL_BRAND_FOOTER_TEXT', '#e5e7eb'),
    'socials' => [
        'twitter' => env('MAIL_BRAND_TWITTER', ''),
        'linkedin' => env('MAIL_BRAND_LINKEDIN', ''),
        'facebook' => env('MAIL_BRAND_FACEBOOK', ''),
        'instagram' => env('MAIL_BRAND_INSTAGRAM', ''),
    ],
    'contact' => [
        'email' => env('MAIL_BRAND_CONTACT_EMAIL', 'support@texa.ng'),
        'address' => env('MAIL_BRAND_ADDRESS', ''),
    ],
];
