<?php

declare(strict_types=1);

namespace App\Support\Api;

use RuntimeException;

/**
 * A failure that the API reports to clients as `{ "error": { code, message, details } }` (PROJECT1_PLAN.md §4.6).
 * `message` is Arabic and safe to show to the user.
 */
final class ApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly array $details = [],
        public readonly array $headers = [],
    ) {
        parent::__construct($message);
    }

    /** @param array<string, mixed> $details */
    public static function invalidState(string $message = 'لا يمكن تنفيذ هذا الإجراء في حالة القضية الحالية.', array $details = []): self
    {
        return new self(409, 'invalid_state', $message, $details);
    }

    public static function forbidden(string $code = 'forbidden', string $message = 'ليست لديك صلاحية لتنفيذ هذا الإجراء.'): self
    {
        return new self(403, $code, $message);
    }

    public static function rateLimited(int $retryAfterSeconds): self
    {
        return new self(429, 'rate_limited', 'محاولات كثيرة. حاول مرة أخرى بعد قليل.', ['retry_after_s' => $retryAfterSeconds], ['Retry-After' => (string) $retryAfterSeconds]);
    }

    public static function aiUnavailable(): self
    {
        return new self(503, 'ai_unavailable', 'خدمة التحليل غير متاحة مؤقتًا. حاول مرة أخرى بعد قليل.');
    }
}
