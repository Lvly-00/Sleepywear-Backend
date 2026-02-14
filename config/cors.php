<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | You can safely keep all these routes open for API-based access.
    | Since your frontend (React) uses a different origin, CORS must explicitly
    | allow that frontend domain to access your Laravel backend.
    |
    */

    'paths' => [
        'api/*',
        'login',
        'logout',
        'register',
        'forgot-password',
        'reset-password',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Use environment variables so it works on both localhost and production.
    | Example:
    | FRONTEND_URL=https://sleepywear-frontend.onrender.com,http://localhost:5173
    |
    */
    'allowed_origins' => [
        'http://localhost:5173',
        'http://192.168.100.93:8081',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
