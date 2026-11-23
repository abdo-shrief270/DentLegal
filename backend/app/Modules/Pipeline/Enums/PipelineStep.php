<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Enums;

/** The six steps the app shows on the «التحليل الذكي» screen (PROJECT1_PLAN.md §4.3, §4.4). */
enum PipelineStep: string
{
    case Ocr = 'ocr';
    case Masking = 'masking';
    case Retrieval = 'retrieval';
    case Analysis = 'analysis';
    case Drafting = 'drafting';
    case Verification = 'verification';

    /** @return array<string, array{status: string, ms: int|null}> */
    public static function initial(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s): array => [$s->value => ['status' => 'pending', 'ms' => null]])->all();
    }
}
