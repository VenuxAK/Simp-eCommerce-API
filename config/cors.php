<?php

$allowedOrigins = env('CORS_ALLOWED_ORIGINS');

if ($allowedOrigins) {
    $origins = explode(',', $allowedOrigins);
} else {
    $origins = [
        env('ADMIN_URL', 'http://localhost:5173'),
        env('STOREFRONT_URL', 'http://localhost:3000'),
    ];
}

$originPatterns = [];

if (env('CORS_ALLOW_LOCALHOST', true)) {
    $originPatterns[] = '#^http://localhost:\d+$#';
}

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $origins,

    'allowed_origins_patterns' => $originPatterns,

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
