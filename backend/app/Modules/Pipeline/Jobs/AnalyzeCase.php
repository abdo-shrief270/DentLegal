<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Jobs;

use App\Modules\Ai\AiService;
use App\Modules\Citations\Support\CitationRecorder;
use App\Modules\Pipeline\Enums\PipelineStep;
use App\Modules\Pipeline\Models\Analysis;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\PipelineFailure;
use App\Modules\Pipeline\Support\AiRunRecorder;
use App\Modules\Pipeline\Support\CaseBundle;
use Illuminate\Support\Facades\DB;

/** Steps «retrieval» and «analysis»: the masked bundle goes to /v1/case/analyze; the result becomes the current analysis. */
final class AnalyzeCase extends PipelineStepJob
{
    protected function run(PipelineJob $job): void
    {
        $case = $job->case;
        $started = hrtime(true);
        $job->stepStarted(PipelineStep::Retrieval);
        $response = app(AiService::class)->analyzeCase(CaseBundle::case($case), CaseBundle::documents($case));
        $job->stepDone(PipelineStep::Retrieval, null);

        if (($response['status'] ?? null) === 'refused' || empty($response['analysis'])) {
            app(AiRunRecorder::class)->record($response['run'] ?? [], $response['sources'] ?? [], 'refused', $case);
            throw new PipelineFailure('ai_refused');
        }

        DB::transaction(function () use ($job, $case, $response): void {
            $a = $response['analysis'];
            Analysis::query()->where('case_id', $case->id)->where('is_current', true)->update(['is_current' => false]);
            $analysis = Analysis::query()->create([
                'case_id' => $case->id,
                'pipeline_job_id' => $job->id,
                'position_strength' => (int) $a['position_strength'],
                'risk_level' => (string) $a['risk_level'],
                'result_json' => $a,
                'source_chunk_ids' => $response['source_chunk_ids'] ?? [],
                'sources_sufficient' => (bool) $a['sources_sufficient'],
                'needs_attention' => $response['status'] === 'needs_attention',
                'is_current' => true,
            ]);
            $run = app(AiRunRecorder::class)->record($response['run'], $response['sources'] ?? [], (string) $response['status'], $analysis);
            $analysis->update(['ai_run_id' => $run?->id]);
            CitationRecorder::analysis($analysis->id, $case->id, $a);
        });
        $job->stepDone(PipelineStep::Analysis, (int) ((hrtime(true) - $started) / 1_000_000));
    }
}
