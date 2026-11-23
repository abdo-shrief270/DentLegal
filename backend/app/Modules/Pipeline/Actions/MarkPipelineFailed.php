<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Actions;

use App\Modules\Ai\AiServiceException;
use App\Modules\Audit\AuditLogger;
use App\Modules\Cases\Actions\TransitionCase;
use App\Modules\Cases\Enums\CaseStatus;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\PipelineFailure;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Marks a run and its case failed with a code the app can show («إعادة المحاولة» resumes from the failed step). */
final class MarkPipelineFailed
{
    public function __construct(private readonly TransitionCase $transition, private readonly AuditLogger $audit) {}

    public function __invoke(string $pipelineJobId, ?Throwable $e): void
    {
        $code = match (true) {
            $e instanceof PipelineFailure => $e->errorCode,
            $e instanceof AiServiceException => $e->errorCode,
            default => 'internal_error',
        };

        DB::transaction(function () use ($pipelineJobId, $code): void {
            /** @var PipelineJob|null $job */
            $job = PipelineJob::query()->with('case.dentist')->lockForUpdate()->find($pipelineJobId);
            if ($job === null || $job->status === 'failed' || $job->status === 'succeeded') {
                return;
            }
            $steps = $job->steps;
            if ($job->current_step !== null && ($steps[$job->current_step]['status'] ?? null) === 'running') {
                $steps[$job->current_step]['status'] = 'failed';
            }
            $job->forceFill(['status' => 'failed', 'error_code' => $code, 'steps' => $steps, 'finished_at' => now()])->save();
            if ($job->case->status === CaseStatus::Analyzing) {
                ($this->transition)($job->case, CaseStatus::Failed, ['pipeline_job_id' => $job->id], $code);
            }
            $this->audit->record('analysis.failed', $job->case->dentist, $job->case, ['pipeline_job_id' => $job->id, 'error_code' => $code]);
        });
    }
}
