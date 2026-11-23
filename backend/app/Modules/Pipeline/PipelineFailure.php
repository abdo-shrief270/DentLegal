<?php

declare(strict_types=1);

namespace App\Modules\Pipeline;

use RuntimeException;

/** A pipeline outcome that retrying won't change (e.g. the model refused). Carries the code shown to the app. */
final class PipelineFailure extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message = '')
    {
        parent::__construct($message !== '' ? $message : $errorCode);
    }
}
