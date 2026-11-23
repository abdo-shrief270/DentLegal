<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Jobs;

use App\Modules\Audit\AuditLogger;
use App\Modules\Cases\Actions\TransitionCase;
use App\Modules\Cases\Enums\CaseStatus;
use App\Modules\Pipeline\Models\PipelineJob;

/** Last link: the case becomes ai_ready and the run succeeded. (The «رد جاهز» push is added with S5.5.) */
final class FinalizeCase extends PipelineStepJob
{
    protected function run(PipelineJob $job): void
    {
        app(TransitionCase::class)($job->case, CaseStatus::AiReady, ['pipeline_job_id' => $job->id]);
        $job->forceFill(['status' => 'succeeded', 'current_step' => null, 'finished_at' => now()])->save();
        app(AuditLogger::class)->record('analysis.completed', $job->case->dentist, $job->case, ['pipeline_job_id' => $job->id]);
    }
}
