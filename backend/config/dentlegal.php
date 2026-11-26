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
        // OCR runs at about 3 s per scanned page on CPU; a 50-page case document stays well inside this.
        'extract_timeout' => (int) env('AI_SERVICE_EXTRACT_TIMEOUT', 300),
        // A 40-page source with OCR-guided repair took about 2 minutes in testing; Claude transcription is slower.
        'ingest_timeout' => (int) env('AI_SERVICE_INGEST_TIMEOUT', 1800),
        'retries' => 2,
    ],

    'push' => [
        // log until the client's Firebase project exists (§8.8); fcm with the service-account JSON on the server.
        'driver' => env('PUSH_DRIVER', 'log'),
        'fcm_credentials' => env('FCM_CREDENTIALS', storage_path('app/private/fcm-service-account.json')),
    ],

    'documents' => [
        'disk' => env('DOCUMENTS_DISK', 'documents'),
        // 32 random bytes, base64. Separate from APP_KEY; kept offline with the backup key (§8.7). Losing it loses the files.
        'master_key' => env('DOCUMENT_MASTER_KEY'),
    ],

    'consultant' => [
        // §4.7: per dentist.
        'per_hour' => 20,
        'per_day' => 100,
        'history_turns' => 3,
        // «أسئلة مقترحة» on the empty chat. Admin-editable with the admin API; these are the defaults.
        'suggested_questions' => [
            'هل يحق لي الإفصاح عن ملف المريض للدفاع عن نفسي؟',
            'هل التأمين ضد الأخطاء الطبية إلزامي لطبيب الأسنان؟',
            'ما المهلة المتاحة للتظلم من قرار الهيئة الصحية الشرعية؟',
            'ما الذي يُعد خطأً مهنيًا صحيًا؟',
            'هل يمكن تأجيل سفري بسبب شكوى؟ وكيف أعترض؟',
        ],
    ],

    'invitations' => [
        'dentist_days' => 30,
        'dashboard_days' => 7,
        // The SMS a dentist receives with the invite; the store links come with D9/D10.
        'app_download_url' => env('APP_DOWNLOAD_URL', 'https://dentlegal.app/download'),
        'dashboard_url' => env('DASHBOARD_URL', 'http://localhost:3000'),
        'resend_cooldown_s' => 60,
    ],

    'queues' => [
        // The queue connection for long AI jobs (`redis-long` in config/queue.php); tests use the default (sync).
        'long_connection' => env('QUEUE_LONG_CONNECTION', 'redis-long'),
    ],

    'knowledge' => [
        'disk' => env('KNOWLEDGE_DISK', 'knowledge'),
        'max_mb' => 50,
    ],

    // Daily AI health digest (PROJECT1_PLAN.md §8.6): thresholds that flag a day, and who gets the email.
    'ai_health' => [
        'recipients' => array_values(array_filter(array_map('trim', explode(',', (string) env('AI_HEALTH_RECIPIENTS', ''))))),
        'webhook_url' => env('ALERT_WEBHOOK_URL'),
        'refusal_rate_max' => 0.10,
        'citation_validity_min' => 0.95,
        'daily_cost_max_usd' => (float) env('AI_DAILY_COST_ALERT_USD', 20),
        // Rates on fewer questions (answered + refused) than this are reported but not flagged (one in three is noise).
        'min_answers' => 10,
    ],

    // §9.7 (proposal; D18 to confirm). The grace period lets support undo a mistaken deletion; it stays under 30 days.
    'retention' => [
        'otp_hours' => 24,
        'token_idle_days' => 30,
        'account_grace_days' => (int) env('RETENTION_ACCOUNT_GRACE_DAYS', 7),
        'ai_runs_months' => 12,
        'audit_years' => 2,
    ],

    'cases' => [
        'code_prefix' => 'DL',
    ],
];
