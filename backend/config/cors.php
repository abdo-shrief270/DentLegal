<?php

/*
| The dashboard (portal.<domain>) calls the API with the session cookie (Sanctum SPA mode, PROJECT1_PLAN.md §4.1),
| so CORS allows exactly that origin with credentials. The mobile app is not a browser and needs no CORS.
*/
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'two-factor-challenge', 'user', 'user/*', 'invitations/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => array_values(array_filter([env('DASHBOARD_URL', 'http://localhost:3000')])),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Content-Type', 'X-XSRF-TOKEN', 'X-Requested-With', 'Idempotency-Key'],
    'exposed_headers' => ['Retry-After'],
    'max_age' => 3600,
    'supports_credentials' => true,
];
