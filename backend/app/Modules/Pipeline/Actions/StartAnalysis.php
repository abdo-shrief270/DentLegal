<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Cases\Actions\TransitionCase;
use App\Modules\Cases\Enums\CaseStatus;
use App\Modules\Cases\Models\DentalCase;
use App\Modules\Documents\Models\Document;
use App\Modules\Identity\Enums\ConsentType;
use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Enums\PipelineStep;
use App\Modules\Pipeline\Jobs\AnalyzeCase;
use App\Modules\Pipeline\Jobs\DraftCaseResponse;
use App\Modules\Pipeline\Jobs\ExtractCaseDocuments;
use App\Modules\Pipeline\Jobs\FinalizeCase;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Support\Api\ApiException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * POST /cases/{case}/analysis (PROJECT1_PLAN.md §4.3, §4.4). Requires the AI-processing consent and at least one
 * document. An Idempotency-Key returns the same run on double taps. A failed case can be re-run: documents already
 * extracted are not processed again.
 */
final class StartAnalysis
{
    public const PER_DAY = 10;

    public function __construct(private readonly TransitionCase $transition, private readonly AuditLogger $audit) {}

    public function __invoke(User $dentist, DentalCase $case, ?string $idempotencyKey): PipelineJob
    {
        if ($idempotencyKey !== null) {
            $existing = PipelineJob::query()->where('case_id', $case->id)->where('idempotency_key', $idempotencyKey)->first();
            if ($existing !== null) {
                return $existing;
            }
        }
        if (! $dentist->hasConsent(ConsentType::AiProcessing)) {
            throw ApiException::forbidden('consent_required', 'يلزم الموافقة على المعالجة الآلية للمستندات قبل بدء التحليل.');
        }
        if (Document::query()->where('case_id', $case->id)->doesntExist()) {
            throw new ApiException(422, 'documents_required', 'أضف مستندًا واحدًا على الأقل قبل بدء التحليل.');
        }
        $key = 'analysis:'.$dentist->id;
        if (RateLimiter::tooManyAttempts($key, self::PER_DAY)) {
            throw ApiException::rateLimited(RateLimiter::availableIn($key));
        }

        $job = DB::transaction(function () use ($dentist, $case, $idempotencyKey): PipelineJob {
            ($this->transition)($case, CaseStatus::Analyzing);
            $job = PipelineJob::query()->create([
                'case_id' => $case->id, 'status' => 'queued', 'steps' => PipelineStep::initial(), 'idempotency_key' => $idempotencyKey,
            ]);
            $this->audit->record('analysis.started', $dentist, $case, ['pipeline_job_id' => $job->id]);

            return $job;
        });
        RateLimiter::hit($key, 86400);

        $id = $job->id;
        Bus::chain([new ExtractCaseDocuments($id), new AnalyzeCase($id), new DraftCaseResponse($id), new FinalizeCase($id)])
            ->catch(function (Throwable $e) use ($id): void {
                app(MarkPipelineFailed::class)($id, $e);
            })
            ->onQueue('ai')
            ->dispatch();

        return $job;
    }
}
