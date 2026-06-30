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

// Enforce that localhost is never allowed by default in production environments
$allowLocalhost = env('CORS_ALLOW_LOCALHOST', env('APP_ENV') !== 'production');

if ($allowLocalhost) {
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
