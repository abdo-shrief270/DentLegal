<?php

namespace Tests\Feature\Pipeline;

use App\Modules\Cases\Enums\CaseStatus;
use App\Modules\Cases\Models\DentalCase;
use App\Modules\Documents\Actions\UploadDocument;
use App\Modules\Documents\Enums\DocumentKind;
use App\Modules\Identity\Enums\ConsentType;
use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Jobs\AnalyzeCase;
use App\Modules\Pipeline\Jobs\DraftCaseResponse;
use App\Modules\Pipeline\Jobs\ExtractCaseDocuments;
use App\Modules\Pipeline\Jobs\FinalizeCase;
use App\Modules\Pipeline\Models\PipelineJob;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** The case pipeline against a faked ai-svc. Synthetic case and documents only. */
class AnalysisPipelineTest extends TestCase
{
    private User $dentist;

    private DentalCase $case;

    /** What the faked /v1/case/analyze answers; a closure reads it, so a test can change it between runs. */
    private string $analyzeStatus = 'ok';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
        $this->dentist = $this->actingAsDentist();
        $this->case = DentalCase::factory()->for($this->dentist, 'dentist')->create(['patient_code' => 'P-7731']);
        app(UploadDocument::class)($this->dentist, $this->case, UploadedFile::fake()->createWithContent('letter.pdf', '%PDF-1.7 synthetic'), DocumentKind::ComplaintLetter);
    }

    private function consent(ConsentType ...$types): void
    {
        foreach ($types as $type) {
            $this->dentist->consents()->create(['type' => $type, 'version' => config('dentlegal.consent_versions.'.$type->value), 'granted_at' => now()]);
        }
    }

    private function fakeAiSvc(string $analyzeStatus = 'ok'): void
    {
        $this->analyzeStatus = $analyzeStatus;
        $llm = [['model_requested' => 'claude-opus-5-5', 'model_served' => 'claude-opus-5-5', 'effort' => 'high', 'inference_geo' => 'us',
            'fallback_used' => false, 'stop_reason' => 'end_turn', 'input_tokens' => 9000, 'output_tokens' => 2500, 'cache_read_tokens' => 2000,
            'cache_write_tokens' => 0, 'cost_usd' => 0.095, 'latency_ms' => 41000, 'anthropic_request_id' => 'req_x']];
        $sources = [['ref' => 'S1', 'chunk_id' => 412, 'label' => 'المادة 19', 'rerank_score' => 0.81, 'dense_rank' => 1, 'sparse_rank' => 2, 'used_in_prompt' => true]];
        Http::fake([
            'ai-svc.test/v1/extract' => Http::response(['mime' => 'application/pdf', 'page_count' => 1, 'ocr_confidence' => 0.91, 'low_quality' => false,
                'pages' => [['page_no' => 1, 'text' => 'يطالب [PATIENT] بالتعويض. جواله [PHONE_1].', 'method' => 'ocr', 'confidence' => 0.91, 'low_quality' => false]],
                'entity_counts' => ['PHONE' => 1], 'placeholder_state' => ['counters' => ['PHONE' => 1], 'values' => ['h1' => '[PHONE_1]']]]),
            'ai-svc.test/v1/mask' => Http::response(['masked' => ['يدّعي [PATIENT] استمرار التنميل.'], 'entity_counts' => ['PATIENT' => 1],
                'placeholder_state' => ['counters' => ['PHONE' => 1], 'values' => ['h1' => '[PHONE_1]']], 'residual_pii' => 0]),
            'ai-svc.test/v1/case/analyze' => fn () => Http::response($this->analyzeStatus === 'refused'
                ? ['status' => 'refused', 'analysis' => null, 'sources' => [], 'source_chunk_ids' => [], 'run' => ['run_type' => 'case_analysis', 'llm' => [['stop_reason' => 'refusal', 'refusal_category' => 'general_harms', 'cost_usd' => 0.01]]]]
                : ['status' => 'ok', 'source_chunk_ids' => [412], 'sources' => $sources,
                    'analysis' => ['position_strength' => 72, 'risk_level' => 'medium', 'summary' => 'موقف قابل للدفاع.', 'strengths' => [], 'weaknesses' => [],
                        'recommended_actions' => [], 'missing_information' => [], 'sources_sufficient' => true, 'flags' => [], 'raw' => ['position_strength' => 72]],
                    'run' => ['run_type' => 'case_analysis', 'prompt_version' => 'case-analysis-v1', 'schema_version' => 'v1', 'llm' => $llm, 'citation_validity' => 1.0,
                        'retrieval' => ['k_dense' => 30], 'repaired' => false]]),
            'ai-svc.test/v1/case/draft' => Http::response(['status' => 'ok', 'sources' => $sources,
                'draft' => ['title' => 'إفادة الممارس الصحي', 'intro' => ['text' => 'أتقدم أنا {{DENTIST_NAME}} بشأن [PATIENT].', 'citations' => []],
                    'sections' => [], 'flags' => []],
                'run' => ['run_type' => 'case_draft', 'llm' => $llm, 'citation_validity' => 1.0]]),
        ]);
    }

    private function runChain(PipelineJob $job): void
    {
        foreach ([ExtractCaseDocuments::class, AnalyzeCase::class, DraftCaseResponse::class, FinalizeCase::class] as $class) {
            (new $class($job->id))->handle(); // the queue is faked, so run each step in-process
            if ($job->refresh()->status === 'failed') {
                return;
            }
        }
    }

    public function test_starting_requires_the_ai_processing_consent(): void
    {
        $this->postJson("/api/v1/cases/{$this->case->id}/analysis")->assertForbidden()->assertJsonPath('error.code', 'consent_required');
    }

    public function test_start_queues_the_chain_and_is_idempotent(): void
    {
        Bus::fake();
        $this->consent(ConsentType::AiProcessing);

        $first = $this->withHeader('Idempotency-Key', 'tap-1')->postJson("/api/v1/cases/{$this->case->id}/analysis")->assertStatus(202)->json('data.job_id');
        $again = $this->withHeader('Idempotency-Key', 'tap-1')->postJson("/api/v1/cases/{$this->case->id}/analysis")->assertStatus(202)->json('data.job_id');

        $this->assertSame($first, $again);
        $this->assertSame(CaseStatus::Analyzing, $this->case->refresh()->status);
        Bus::assertChained([ExtractCaseDocuments::class, AnalyzeCase::class, DraftCaseResponse::class, FinalizeCase::class]);
    }

    public function test_full_run_produces_analysis_draft_and_ai_runs(): void
    {
        Queue::fake();
        $this->consent(ConsentType::AiProcessing, ConsentType::LawyerAccess);
        $this->fakeAiSvc();
        $jobId = $this->postJson("/api/v1/cases/{$this->case->id}/analysis")->json('data.job_id');

        $this->runChain(PipelineJob::query()->findOrFail($jobId));

        $this->getJson("/api/v1/cases/{$this->case->id}/analysis/status")->assertOk()
            ->assertJsonPath('data.status', 'succeeded')
            ->assertJsonPath('data.progress', 100)
            ->assertJsonPath('data.steps.0.key', 'ocr');
        $this->getJson("/api/v1/cases/{$this->case->id}/analysis")->assertOk()
            ->assertJsonPath('data.position_strength', 72)
            ->assertJsonMissingPath('data.raw');
        $this->getJson("/api/v1/cases/{$this->case->id}/draft")->assertOk()
            ->assertJsonPath('data.version.no', 1)
            ->assertJsonPath('data.version.intro.text', "أتقدم أنا {$this->dentist->name} بشأن المريض (الرمز P-7731).");

        $this->assertSame(CaseStatus::AiReady, $this->case->refresh()->status);
        $this->assertStringContainsString('[PATIENT]', $this->case->facts_masked);
        $this->assertDatabaseCount('ai_runs', 2);
        $this->assertDatabaseHas('ai_run_sources', ['chunk_id' => 412, 'local_ref' => 'S1']);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v1/case/draft') && $r['source_chunk_ids'] === [412]);
        // Only masked text goes to the analysis: the bundle carries the masked page, never the file.
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v1/case/analyze') && $r['documents'][0]['pages'][0]['text'] === 'يطالب [PATIENT] بالتعويض. جواله [PHONE_1].');

        $this->postJson("/api/v1/cases/{$this->case->id}/submit-review")->assertOk()->assertJsonPath('data.status', 'lawyer_review');
        $this->assertDatabaseHas('drafts', ['case_id' => $this->case->id, 'status' => 'in_review']);
    }

    public function test_a_refusal_fails_the_run_and_the_case_can_be_retried(): void
    {
        Queue::fake();
        $this->consent(ConsentType::AiProcessing);
        $this->fakeAiSvc('refused');
        $jobId = $this->postJson("/api/v1/cases/{$this->case->id}/analysis")->json('data.job_id');

        $this->runChain(PipelineJob::query()->findOrFail($jobId));

        $this->getJson("/api/v1/cases/{$this->case->id}/analysis/status")
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.error', 'ai_refused');
        $this->assertSame(CaseStatus::Failed, $this->case->refresh()->status);
        $this->assertSame('ai_refused', $this->case->failure_code);
        $this->assertDatabaseHas('ai_runs', ['status' => 'refused', 'refusal_category' => 'general_harms']);

        // Retry: documents already extracted are not sent again.
        $this->analyzeStatus = 'ok';
        $retry = $this->postJson("/api/v1/cases/{$this->case->id}/analysis")->assertStatus(202)->json('data.job_id');
        $this->runChain(PipelineJob::query()->findOrFail($retry));
        $this->assertSame(CaseStatus::AiReady, $this->case->refresh()->status);
        $calls = collect(Http::recorded())->map(fn (array $pair): string => basename($pair[0]->url()))->countBy()->all();
        $this->assertSame(['extract' => 1, 'mask' => 2, 'analyze' => 2, 'draft' => 1], $calls);
    }

    public function test_submitting_for_review_needs_the_lawyer_access_consent(): void
    {
        $this->case->forceFill(['status' => CaseStatus::AiReady])->save();

        $this->postJson("/api/v1/cases/{$this->case->id}/submit-review")->assertForbidden()->assertJsonPath('error.code', 'consent_required');
    }
}
