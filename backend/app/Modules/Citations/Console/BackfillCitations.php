<?php

declare(strict_types=1);

namespace App\Modules\Citations\Console;

use App\Modules\Citations\Support\CitationRecorder;
use App\Modules\Consultations\Models\ConsultationMessage;
use App\Modules\Pipeline\Models\Analysis;
use App\Modules\Pipeline\Models\DraftVersion;
use Illuminate\Console\Command;

/** Records citations for AI output stored before the citations table existed. Safe to run again. */
final class BackfillCitations extends Command
{
    protected $signature = 'citations:backfill';

    protected $description = 'Record citation rows for existing AI answers, analyses and AI draft versions';

    public function handle(): int
    {
        $n = 0;
        ConsultationMessage::query()->where('role', 'assistant')->where('status', 'answered')->lazyById()->each(function (ConsultationMessage $m) use (&$n): void {
            CitationRecorder::consult($m->id, $m->answer_json ?? []);
            $n++;
        });
        Analysis::query()->lazyById()->each(function (Analysis $a) use (&$n): void {
            CitationRecorder::analysis($a->id, $a->case_id, $a->result_json);
            $n++;
        });
        DraftVersion::query()->where('author_type', 'ai')->with('draft')->lazyById()->each(function (DraftVersion $v) use (&$n): void {
            CitationRecorder::draft($v->id, $v->draft->case_id, $v->content_json);
            $n++;
        });
        $this->info("{$n} outputs scanned");

        return self::SUCCESS;
    }
}
