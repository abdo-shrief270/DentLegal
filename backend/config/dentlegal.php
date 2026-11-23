<?php

return [
    // Oldest app build that may call the API; older ones get 426 app_update_required.
    'min_app_version' => env('DENTLEGAL_MIN_APP_VERSION', '1.0.0'),

    'otp' => [
        'length' => 6,
        'ttl_seconds' => 300,
        'resend_after_seconds' => 60,
        'max_attempts' => 5,
        // Rate limits from PROJECT1_PLAN.md §4.7.
        'per_phone_per_hour' => 5,
        'per_ip_per_hour' => 20,
        'verify_per_phone_per_hour' => 10,
        // App Store review number with a fixed code. Off unless both are set (§8.9).
        'review_phone' => env('OTP_REVIEW_PHONE'),
        'review_code' => env('OTP_REVIEW_CODE'),
    ],

    'tokens' => [
        'abilities' => ['dentist'],
        'ttl_days' => 30,
    ],

    // Bump a version when its text changes; users are asked again.
    'consent_versions' => [
        'terms' => '2026-11',
        'privacy' => '2026-11',
        'ai_processing' => '2026-11',
        'lawyer_access' => '2026-11',
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'log_channel' => 'sms',
    ],

    'ai_service' => [
        'url' => env('AI_SERVICE_URL', 'http://ai-svc:8000'),
        'token' => env('AI_SERVICE_TOKEN'),
        'connect_timeout' => 3,
        // Long routes (analysis, draft) are called from queued jobs with their own timeout.
        'timeout' => (int) env('AI_SERVICE_TIMEOUT', 30),
        'consult_timeout' => 90,
        // Analysis and draft run with effort high and up to 32k output tokens, plus one repair call (§5.6).
        'case_timeout' => (int) env('AI_SERVICE_CASE_TIMEOUT', 600),
        'retries' => 2,
    ],

    'cases' => [
        'code_prefix' => 'DL',
    ],
];
