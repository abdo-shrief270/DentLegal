<?php

namespace Tests\Feature\Consultations;

use App\Modules\Consultations\Jobs\AnswerConsultation;
use App\Modules\Consultations\Models\ConsultationMessage;
use App\Modules\Identity\Enums\ConsentType;
use App\Modules\Identity\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/** The consultant API against a faked ai-svc. Synthetic questions only. */
class ConsultationApiTest extends TestCase
{
    private User $dentist;

    private string $consultStatus = 'answered';

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->dentist = $this->actingAsDentist();
        $this->dentist->consents()->create(['type' => ConsentType::AiProcessing, 'version' => config('dentlegal.consent_versions.ai_processing'), 'granted_at' => now()]);
        Http::fake([
            'ai-svc.test/v1/mask' => fn (Request $r) => Http::response([
                'masked' => array_map(fn (string $t): string => str_replace('سارة', '[PERSON_1]', $t), $r['texts']),
                'entity_counts' => [], 'placeholder_state' => ['counters' => ['PERSON' => 1], 'values' => ['h' => '[PERSON_1]']], 'residual_pii' => 0,
            ]),
            'ai-svc.test/v1/consult' => fn () => $this->consultStatus === 'error'
                ? Http::response(['error' => ['code' => 'ai_invalid_output', 'message' => 'x', 'retryable' => false]], 422)
                : Http::response($this->consultResponse()),
        ]);
    }

    /** @return array<string, mixed> */
    private function consultResponse(): array
    {
        $refused = str_starts_with($this->consultStatus, 'refused');

        return [
            'status' => $this->consultStatus,
            'answer' => [
                'answerable' => ! $refused,
                'paragraphs' => [[
                    'text' => $refused ? 'لا تتوفر لديّ مصادر نظامية كافية.' : 'نعم، بالقدر اللازم للدفاع عن نفسك يا {{DENTIST_NAME}}.',
                    'legal_claim' => ! $refused, 'unsupported' => false, 'article_mismatch' => false,
                    'citations' => $refused ? [] : [['ref' => 'S1', 'kind' => 'knowledge', 'chunk_id' => 412, 'label' => 'المادة 21', 'quote' => 'دفع اتهام', 'verified' => true]],
                ]],
                'suggest_lawyer' => $refused, 'follow_ups' => [], 'flags' => [],
            ],
            'sources' => [
                ['ref' => 'S1', 'chunk_id' => 412, 'label' => 'المادة 21', 'law' => 'نظام مزاولة المهن الصحية', 'rerank_score' => 0.8, 'used_in_prompt' => true],
                ['ref' => 'S2', 'chunk_id' => 413, 'label' => 'المادة 22', 'law' => 'نظام مزاولة المهن الصحية', 'rerank_score' => 0.5, 'used_in_prompt' => true],
            ],
            'run' => $this->consultStatus === 'refused_retrieval' ? ['run_type' => 'consult', 'llm' => []] : ['run_type' => 'consult', 'prompt_version' => 'consult-v1', 'citation_validity' => 1.0,
                'llm' => [['model_requested' => 'claude-opus-5-5', 'model_served' => 'claude-opus-5-5', 'effort' => 'low', 'stop_reason' => 'end_turn', 'input_tokens' => 3400, 'output_tokens' => 400, 'cost_usd' => 0.05]]],
        ];
    }

    private function answer(string $messageId): void
    {
        app()->call([new AnswerConsultation($messageId), 'handle']);
    }

    public function test_suggested_questions_come_from_config(): void
    {
        $this->getJson('/api/v1/consultations/suggested-questions')->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_asking_masks_the_question_and_returns_a_pending_answer(): void
    {
        $res = $this->postJson('/api/v1/consultations', ['question' => 'المريضة سارة جوالها 0551234567، هل يحق لي الإفصاح عن ملفها؟'])
            ->assertStatus(202)->assertJsonPath('data.status', 'pending');

        $question = ConsultationMessage::query()->findOrFail($res->json('data.reply_to'));
        $this->assertStringContainsString('[PHONE_1]', (string) $question->text_masked);
        $this->assertStringNotContainsString('0551234567', (string) $question->text_masked);
        $this->assertStringNotContainsString('0551234567', (string) DB::table('consultation_messages')->where('id', $question->id)->value('text_masked'));
        Queue::assertPushedOn('ai', AnswerConsultation::class);
    }

    public function test_answer_flow_with_polling_history_and_cited_sources(): void
    {
        $first = $this->postJson('/api/v1/consultations', ['question' => 'المريضة سارة اشتكت، هل يحق لي الإفصاح عن ملفها؟'])->json('data');
        $this->answer($first['id']);

        $this->getJson("/api/v1/consultation-messages/{$first['id']}")->assertOk()
            ->assertJsonPath('data.status', 'answered')
            ->assertJsonPath('data.answer.paragraphs.0.text', "نعم، بالقدر اللازم للدفاع عن نفسك يا {$this->dentist->name}.")
            ->assertJsonCount(1, 'data.answer.sources')
            ->assertJsonPath('data.answer.sources.0.chunk_id', 412);
        // NER masking from ai-svc replaced the stored question too.
        $this->assertStringContainsString('[PERSON_1]', (string) ConsultationMessage::query()->findOrFail($first['reply_to'])->text_masked);
        $this->assertDatabaseHas('ai_runs', ['run_type' => 'consult', 'status' => 'succeeded', 'subject_id' => $first['id']]);

        $second = $this->postJson("/api/v1/consultations/{$first['consultation_id']}/messages", ['question' => 'وما الحد المسموح؟'])->assertStatus(202)->json('data');
        $this->answer($second['id']);

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v1/consult') && count($r['history']) === 1
            && str_contains($r['history'][0]['question'], '[PERSON_1]') && $r['question'] === 'وما الحد المسموح؟');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v1/mask') && $r['placeholder_state'] == ['counters' => ['PERSON' => 1], 'values' => ['h' => '[PERSON_1]']]); // jsonb: key order not kept

        $this->getJson("/api/v1/consultations/{$first['consultation_id']}")->assertOk()
            ->assertJsonCount(4, 'data.messages')
            ->assertJsonPath('data.messages.0.role', 'user')
            ->assertJsonPath('data.messages.1.role', 'assistant');
        $this->getJson('/api/v1/consultations')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_one_open_question_per_thread(): void
    {
        $first = $this->postJson('/api/v1/consultations', ['question' => 'سؤال أول عن التأمين'])->json('data');

        $this->postJson("/api/v1/consultations/{$first['consultation_id']}/messages", ['question' => 'سؤال ثانٍ'])
            ->assertStatus(409)->assertJsonPath('error.code', 'answer_pending');
    }

    public function test_idempotency_key_returns_the_same_answer_row(): void
    {
        $a = $this->withHeader('Idempotency-Key', 'tap-1')->postJson('/api/v1/consultations', ['question' => 'سؤال عن التأمين'])->json('data.id');
        $b = $this->withHeader('Idempotency-Key', 'tap-1')->postJson('/api/v1/consultations', ['question' => 'سؤال عن التأمين'])->json('data.id');

        $this->assertSame($a, $b);
        $this->assertDatabaseCount('consultations', 1);
    }

    public function test_refusals_are_recorded_without_an_llm_call_when_retrieval_is_weak(): void
    {
        $this->consultStatus = 'refused_retrieval';
        $id = $this->postJson('/api/v1/consultations', ['question' => 'ما رأيك في الطقس؟'])->json('data.id');
        $this->answer($id);

        $this->getJson("/api/v1/consultation-messages/{$id}")
            ->assertJsonPath('data.status', 'refused')
            ->assertJsonPath('data.answer.refusal_reason', 'refused_retrieval')
            ->assertJsonPath('data.answer.suggest_lawyer', true)
            ->assertJsonCount(0, 'data.answer.sources');
        $this->assertDatabaseCount('ai_runs', 0);
    }

    public function test_ai_svc_failures_end_as_failed_with_a_code(): void
    {
        $this->consultStatus = 'error';
        $id = $this->postJson('/api/v1/consultations', ['question' => 'سؤال عن التأمين'])->json('data.id');
        $this->answer($id);

        $this->getJson("/api/v1/consultation-messages/{$id}")->assertJsonPath('data.status', 'failed')->assertJsonPath('data.error', 'ai_invalid_output');
    }

    public function test_consent_and_rate_limits(): void
    {
        $this->dentist->consents()->delete();
        $this->postJson('/api/v1/consultations', ['question' => 'سؤال عن التأمين'])->assertForbidden()->assertJsonPath('error.code', 'consent_required');

        $this->dentist->consents()->create(['type' => ConsentType::AiProcessing, 'version' => config('dentlegal.consent_versions.ai_processing'), 'granted_at' => now()]);
        for ($i = 0; $i < 20; $i++) {
            RateLimiter::hit('consult:h:'.$this->dentist->id, 3600);
        }
        $this->postJson('/api/v1/consultations', ['question' => 'سؤال عن التأمين'])->assertStatus(429)->assertJsonPath('error.code', 'rate_limited');
    }

    public function test_other_dentists_threads_are_not_found(): void
    {
        $first = $this->postJson('/api/v1/consultations', ['question' => 'سؤال عن التأمين'])->json('data');
        $this->actingAsDentist();

        $this->getJson("/api/v1/consultations/{$first['consultation_id']}")->assertNotFound();
        $this->getJson("/api/v1/consultation-messages/{$first['id']}")->assertNotFound();
        $this->getJson('/api/v1/consultations/not-a-uuid')->assertNotFound();
    }
}
