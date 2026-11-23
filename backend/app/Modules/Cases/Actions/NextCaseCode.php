<?php

declare(strict_types=1);

namespace App\Modules\Cases\Actions;

use Illuminate\Support\Facades\DB;

/** DL-2026-0001, DL-2026-0002, … one atomic upsert per code, safe under concurrent requests. */
final class NextCaseCode
{
    public function __invoke(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $row = DB::selectOne(
            'INSERT INTO case_code_counters (year, last_no) VALUES (?, 1)
             ON CONFLICT (year) DO UPDATE SET last_no = case_code_counters.last_no + 1
             RETURNING last_no',
            [$year],
        );

        return sprintf('%s-%d-%04d', config('dentlegal.cases.code_prefix'), $year, (int) $row->last_no);
    }
}
