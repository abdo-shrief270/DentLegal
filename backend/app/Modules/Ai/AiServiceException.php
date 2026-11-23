<?php

declare(strict_types=1);

namespace App\Modules\Ai;

use RuntimeException;

/** ai-svc could not be reached or answered with an error. `retryable` drives job retries. */
final class AiServiceException extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode = 'ai_unavailable', public readonly bool $retryable = true, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
