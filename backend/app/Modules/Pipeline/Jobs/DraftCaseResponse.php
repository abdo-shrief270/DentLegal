<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Jobs;

use App\Modules\Ai\AiService;
use App\Modules\Citations\Support\CitationRecorder;
use App\Modules\Pipeline\Enums\PipelineStep;
use App\Modules\Pipeline\Models\Analysis;
use App\Modules\Pipeline\Models\Draft;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\PipelineFailure;
use App\Modules\Pipeline\Support\AiRunRecorder;
use App\Modules\Pipeline\Support\CaseBundle;
use Illuminate\Support\Facades\DB;

/** Steps «drafting» and «verification»: /v1/case/draft from the current analysis; the result is a new AI draft version. */
final class DraftCaseResponse extends PipelineStepJob
{
    protected function run(PipelineJob $job): void
    {
        $case = $job->case;
        $analysis = Analysis::query()->where('case_id', $case->id)->where('is_current', true)->firstOrFail();
        $started = hrtime(true);
        $job->stepStarted(PipelineStep::Drafting);
        $response = app(AiService::class)->draftCase(
            CaseBundle::case($case), CaseBundle::documents($case), $analysis->result_json['raw'] ?? [], $analysis->source_chunk_ids,
        );
        if (($response['status'] ?? null) === 'refused' || empty($response['draft'])) {
            app(AiRunRecorder::class)->record($response['run'] ?? [], $response['sources'] ?? [], 'refused', $case);
            throw new PipelineFailure('ai_refused');
        }
        $job->stepDone(PipelineStep::Drafting, (int) ((hrtime(true) - $started) / 1_000_000));

        $job->stepStarted(PipelineStep::Verification);
        DB::transaction(function () use ($case, $analysis, $response): void {
            /** @var Draft $draft */
            $draft = Draft::query()->lockForUpdate()->firstOrCreate(['case_id' => $case->id], ['status' => 'ai_generated']);
            $versionNo = (int) $draft->versions()->max('version_no') + 1;
            $run = app(AiRunRecorder::class)->record($response['run'], $response['sources'] ?? [], (string) $response['status'], $draft);
            $version = $draft->versions()->create([
                'version_no' => $versionNo, 'author_type' => 'ai', 'content_json' => $response['draft'] + ['needs_attention' => $response['status'] === 'needs_attention'],
                'ai_run_id' => $run?->id,
            ]);
            $draft->update(['current_version_id' => $version->id, 'analysis_id' => $analysis->id, 'status' => 'ai_generated']);
            CitationRecorder::draft($version->id, $case->id, $version->content_json);
        });
        $job->stepDone(PipelineStep::Verification, null);
    }
}
