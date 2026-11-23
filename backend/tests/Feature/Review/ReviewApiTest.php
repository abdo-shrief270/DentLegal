<?php

namespace Tests\Feature\Review;

use App\Modules\Cases\Enums\CaseStatus;
use App\Modules\Cases\Models\DentalCase;
use App\Modules\Documents\Models\Document;
use App\Modules\Identity\Http\Controllers\Dashboard\DashboardAuthController;
use App\Modules\Identity\Models\User;
use App\Modules\Knowledge\Models\KnowledgeSource;
use App\Modules\Knowledge\Models\KnowledgeSourceVersion;
use App\Modules\Pipeline\Models\Analysis;
use App\Modules\Pipeline\Models\Draft;
use App\Modules\Pipeline\Support\AiRunRecorder;
use App\Modules\Review\Events\DraftApproved;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Lawyer review API (PROJECT1_PLAN.md §4.2, S4.2–S4.4). Synthetic case, documents and regulation text only. */
class ReviewApiTest extends TestCase
{
    private const ARTICLE = 'يجب على الممارس الصحي أن يحافظ على الأسرار التي علم بها عن طريق مهنته، ولا يجوز له إفشاؤها إلا لدفع اتهام وجهه إليه المريض.';

    private Draft $draft;

    private DentalCase $case;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['Origin' => 'http://localhost', 'Referer' => 'http://localhost/']);

        $source = KnowledgeSource::query()->create(['code' => 'HPPL', 'title_ar' => 'نظام مزاولة المهن الصحية', 'type' => 'law']);
        $version = KnowledgeSourceVersion::query()->create(['source_id' => $source->id, 'version_label' => '1440', 'status' => 'review']);
        $chunkId = (int) DB::selectOne(
            "INSERT INTO knowledge_chunks (version_id, source_id, status, chunk_key, ordinal, chunk_type, article_no, display_text, search_text, token_count, heading_path)
             VALUES (?, ?, 'review', 'HPPL:1440:A21', 1, 'article', 21, ?, ?, 40, '{\"الفصل الثالث\",\"واجبات الممارس الصحي\"}') RETURNING id",
            [$version->id, $source->id, self::ARTICLE, self::ARTICLE],
        )->id;

        $this->case = DentalCase::factory()->status(CaseStatus::LawyerReview)->create(['patient_code' => 'P-7731', 'submitted_for_review_at' => now()->subHour()]);
        $doc = (new Document)->forceFill([
            'case_id' => $this->case->id, 'kind' => 'complaint_letter', 'original_name' => 'letter.pdf', 'mime' => 'application/pdf', 'size_bytes' => 100,
            'storage_key' => 'x', 'wrapped_key' => 'x', 'sha256' => str_repeat('0', 64), 'page_count' => 1, 'ocr_status' => 'done', 'ocr_confidence' => 0.91,
        ]);
        $doc->save();
        $doc->pages()->create(['page_no' => 1, 'text_masked' => 'يدّعي [PATIENT] استمرار التنميل في الشفة السفلية.', 'confidence' => 0.91, 'method' => 'ocr', 'masked_entity_counts' => []]);

        $run = app(AiRunRecorder::class)->record(['run_type' => 'case_draft', 'llm' => [['model_requested' => 'claude-opus-5-5', 'cost_usd' => 0.2]]],
            [['ref' => 'S1', 'chunk_id' => $chunkId, 'rerank_score' => 0.83]], 'succeeded');
        Analysis::query()->create(['case_id' => $this->case->id, 'position_strength' => 72, 'risk_level' => 'medium', 'result_json' => ['summary' => 'موقف قابل للدفاع.'],
            'source_chunk_ids' => [$chunkId], 'sources_sufficient' => true, 'needs_attention' => false, 'is_current' => true]);
        $this->draft = Draft::query()->create(['case_id' => $this->case->id, 'status' => 'in_review']);
        $v1 = $this->draft->versions()->create(['version_no' => 1, 'author_type' => 'ai', 'ai_run_id' => $run?->id, 'content_json' => $this->content('إفادة الممارس الصحي')]);
        $this->draft->update(['current_version_id' => $v1->id]);
    }

    /** @return array<string, mixed> */
    private function content(string $title, string $quote = 'لا يجوز له إفشاؤها إلا لدفع اتهام'): array
    {
        $para = fn (string $text, bool $legal, array $citations) => ['text' => $text, 'legal_claim' => $legal, 'citations' => $citations];

        return [
            'title' => $title, 'addressee' => 'سعادة مدير الشؤون الصحية', 'salutation' => 'السلام عليكم', 'closing' => 'وتقبلوا التحية', 'attachments' => [],
            'intro' => $para('أتقدم أنا {{DENTIST_NAME}} بإفادتي بشأن شكوى [PATIENT].', false, []),
            'sections' => [
                ['kind' => 'facts', 'heading' => 'أولًا: الوقائع', 'paragraphs' => [$para('ادعى المريض استمرار التنميل.', false, [['ref' => 'D1', 'quote' => 'استمرار التنميل في الشفة السفلية']])]],
                ['kind' => 'defenses', 'heading' => 'ثانيًا: الدفوع', 'paragraphs' => [$para('يجوز الإفصاح بالقدر اللازم للدفاع.', true, [['ref' => 'S1', 'quote' => $quote]])]],
            ],
        ];
    }

    private function asLawyer(?User $lawyer = null): User
    {
        $lawyer ??= User::factory()->lawyer()->create();
        $lawyer->forceFill(['two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'two_factor_confirmed_at' => now()])->save();
        $this->app['auth']->forgetGuards();
        $this->flushSession(); // a new person signs in with a new session (Sanctum ties a session to one password hash)
        $this->actingAs($lawyer, 'web')->withSession([DashboardAuthController::PASSED => time()]);

        return $lawyer;
    }

    private function url(string $suffix = ''): string
    {
        return "/api/v1/lawyer/reviews/{$this->draft->id}{$suffix}";
    }

    public function test_queue_tabs_and_counts(): void
    {
        $lawyer = $this->asLawyer();

        $this->getJson('/api/v1/lawyer/reviews?tab=unclaimed')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.case_code', $this->case->code)->assertJsonPath('data.0.risk', 'medium')->assertJsonPath('data.0.position_strength', 72);
        $this->postJson($this->url('/claim'))->assertOk()->assertJsonPath('data.claimed_by', $lawyer->id);
        $this->getJson('/api/v1/lawyer/reviews/counts')->assertOk()->assertJsonPath('data.unclaimed', 0)->assertJsonPath('data.mine', 1)->assertJsonPath('data.approved', 0);
    }

    public function test_detail_has_sources_documents_versions_and_checks(): void
    {
        $this->asLawyer();

        $this->getJson($this->url())->assertOk()
            ->assertJsonPath('data.patient_code', 'P-7731')
            ->assertJsonPath('data.sources.0.ref', 'S1')
            ->assertJsonPath('data.sources.0.label', 'المادة 21')
            ->assertJsonPath('data.sources.0.title', 'واجبات الممارس الصحي')
            ->assertJsonPath('data.sources.0.law', 'نظام مزاولة المهن الصحية')
            ->assertJsonPath('data.documents.0.ref', 'D1')
            ->assertJsonPath('data.versions.0.author_name', 'DentLegal AI')
            ->assertJsonPath('data.versions.0.content.intro.id', 'intro')
            ->assertJsonPath('data.versions.0.content.sections.1.paragraphs.0.id', 's1p0')
            ->assertJsonPath('data.versions.0.content.intro.text', 'أتقدم أنا {{DENTIST_NAME}} بإفادتي بشأن شكوى [PATIENT].')
            ->assertJsonCount(4, 'data.checks');
    }

    public function test_claiming_is_first_come_first_served(): void
    {
        $this->asLawyer();
        $this->postJson($this->url('/claim'))->assertOk();
        $this->postJson($this->url('/claim'))->assertOk(); // same lawyer again: fine

        $this->asLawyer();
        $this->postJson($this->url('/claim'))->assertStatus(409)->assertJsonPath('error.code', 'already_claimed');
        $this->postJson($this->url('/notes'), ['text' => 'ملاحظة'])->assertForbidden()->assertJsonPath('error.code', 'not_claimed_by_you');
    }

    public function test_lawyer_saves_a_checked_version_adds_a_note_and_approves(): void
    {
        Event::fake([DraftApproved::class]);
        $lawyer = $this->asLawyer();
        $this->postJson($this->url('/claim'));

        $bad = $this->content('إفادة معدلة', 'نص غير موجود في المادة');
        $this->postJson($this->url('/versions'), ['content' => $bad, 'note' => 'تعديل الصياغة'])->assertCreated()
            ->assertJsonCount(2, 'data.versions')
            ->assertJsonPath('data.versions.1.author_type', 'lawyer')
            ->assertJsonPath('data.versions.1.content.sections.1.paragraphs.0.citations.0.verified', false)
            ->assertJsonPath('data.versions.1.content.sections.1.paragraphs.0.unsupported', true)
            ->assertJsonPath('data.versions.1.content.sections.0.paragraphs.0.citations.0.verified', true)
            ->assertJsonPath('data.checks.0.ok', false);

        $this->postJson($this->url('/approve'))->assertUnprocessable()->assertJsonPath('error.code', 'warnings_unconfirmed');

        $this->postJson($this->url('/versions'), ['content' => $this->content('إفادة نهائية'), 'note' => null])->assertCreated()
            ->assertJsonPath('data.versions.2.content.sections.1.paragraphs.0.citations.0.verified', true);
        $this->postJson($this->url('/notes'), ['text' => 'راجعت الاستشهاد بالمادة 21.'])->assertCreated();
        $this->postJson($this->url('/approve'))->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approved_by', $lawyer->name)
            ->assertJsonPath('data.versions.2.locked', true)
            ->assertJsonPath('data.notes.0.text', 'راجعت الاستشهاد بالمادة 21.');

        $this->assertSame(CaseStatus::Approved, $this->case->refresh()->status);
        Event::assertDispatched(DraftApproved::class, fn (DraftApproved $e) => $e->dentistId === $this->case->dentist_id);
        $this->postJson($this->url('/versions'), ['content' => $this->content('بعد الاعتماد')])->assertStatus(409);

        // The dentist now sees the approved lawyer version; internal notes never appear.
        Sanctum::actingAs($this->case->dentist, ['dentist']);
        $this->withHeader('X-App-Version', '1.0.0 (1)')->getJson("/api/v1/cases/{$this->case->id}/draft")->assertOk()
            ->assertJsonPath('data.version.no', 3)
            ->assertJsonPath('data.version.title', 'إفادة نهائية')
            ->assertJsonPath('data.approval.approved_by', $lawyer->name)
            ->assertJsonMissingPath('data.notes');
        $this->getJson("/api/v1/cases/{$this->case->id}")->assertOk()->assertJsonPath('data.lawyer_name', $lawyer->name)->assertJsonPath('data.risk', 'medium');
    }

    public function test_the_dentist_does_not_see_lawyer_work_in_progress(): void
    {
        $this->asLawyer();
        $this->postJson($this->url('/claim'));
        $this->postJson($this->url('/versions'), ['content' => $this->content('مسودة المحامي غير المعتمدة')])->assertCreated();

        Sanctum::actingAs($this->case->dentist, ['dentist']);
        $this->getJson("/api/v1/cases/{$this->case->id}/draft")->assertOk()->assertJsonPath('data.version.no', 1)->assertJsonPath('data.version.title', 'إفادة الممارس الصحي');
    }

    public function test_access_rules(): void
    {
        // Admins and dentists are not reviewers; drafts not yet submitted don't exist for lawyers.
        $admin = User::factory()->create(['phone_e164' => null, 'email' => 'admin@dentlegal.test', 'password' => 'password-123']);
        $admin->assignRole('admin');
        $this->asLawyer($admin);
        $this->getJson($this->url())->assertForbidden();

        $this->asLawyer();
        $this->draft->update(['status' => 'ai_generated']);
        $this->getJson($this->url())->assertNotFound();

        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->getJson('/api/v1/lawyer/reviews')->assertUnauthorized();
    }

    public function test_validation_of_edited_content(): void
    {
        $this->asLawyer();
        $this->postJson($this->url('/claim'));
        $content = $this->content('x');
        $content['sections'][1]['paragraphs'][0]['citations'][0]['ref'] = 'X9';

        $this->postJson($this->url('/versions'), ['content' => $content])->assertUnprocessable()
            ->assertJsonValidationErrors(['content.title', 'content.sections.1.paragraphs.0.citations.0.ref']);
    }
}
