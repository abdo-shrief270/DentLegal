<?php

namespace Tests\Feature\Citations;

use App\Modules\Cases\Models\DentalCase;
use App\Modules\Citations\Models\Citation;
use App\Modules\Citations\Support\CitationRecorder;
use App\Modules\Identity\Http\Controllers\Dashboard\DashboardAuthController;
use App\Modules\Identity\Models\User;
use App\Modules\Knowledge\Models\KnowledgeSource;
use App\Modules\Knowledge\Models\KnowledgeSourceVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Citation records and the lawyer's accuracy sample (PROJECT1_PLAN.md §5.14, S4.6). Synthetic data only. */
class CitationSampleTest extends TestCase
{
    private int $chunkId;

    private DentalCase $case;

    protected function setUp(): void
    {
        parent::setUp();
        $source = KnowledgeSource::query()->create(['code' => 'HPPL', 'title_ar' => 'نظام مزاولة المهن الصحية', 'type' => 'law']);
        $version = KnowledgeSourceVersion::query()->create(['source_id' => $source->id, 'version_label' => '1440', 'status' => 'review']);
        $this->chunkId = (int) DB::selectOne(
            "INSERT INTO knowledge_chunks (version_id, source_id, status, chunk_key, ordinal, chunk_type, article_no, display_text, search_text, token_count)
             VALUES (?, ?, 'review', 'HPPL:1440:A21', 1, 'article', 21, 'يجب على الممارس الصحي أن يحافظ على الأسرار.', 'نص', 10) RETURNING id",
            [$version->id, $source->id],
        )->id;
        $this->case = DentalCase::factory()->create();
    }

    /** @return array<string, mixed> */
    private function cite(bool $verified = true, string $kind = 'knowledge'): array
    {
        return ['ref' => $kind === 'knowledge' ? 'S1' : 'D1', 'kind' => $kind, 'chunk_id' => $kind === 'knowledge' ? $this->chunkId : null,
            'quote' => 'يحافظ على الأسرار', 'verified' => $verified, 'verification' => ['match' => $verified ? 'exact' : 'none']];
    }

    private function seedCitations(int $perRoute): void
    {
        for ($i = 0; $i < $perRoute; $i++) {
            CitationRecorder::consult((string) Str::uuid7(), ['paragraphs' => [['text' => "إجابة {$i}", 'citations' => [$this->cite()]]]]);
            CitationRecorder::analysis((string) Str::uuid7(), $this->case->id, ['strengths' => [['text' => "قوة {$i}", 'citations' => [$this->cite(), $this->cite(true, 'case_document')]]]]);
            CitationRecorder::draft((string) Str::uuid7(), $this->case->id, ['intro' => ['text' => 'مقدمة', 'citations' => []],
                'sections' => [['paragraphs' => [['text' => "دفع {$i}", 'citations' => [$this->cite()]], ['text' => 'غير موثق', 'citations' => [$this->cite(false)]]]]]]);
        }
    }

    private function asLawyer(?User $lawyer = null): User
    {
        $lawyer ??= User::factory()->lawyer()->create(['two_factor_confirmed_at' => now()]);
        $this->withHeaders(['Origin' => 'http://localhost', 'Referer' => 'http://localhost/'])
            ->actingAs($lawyer, 'web')->withSession([DashboardAuthController::PASSED => time()]);

        return $lawyer;
    }

    public function test_ai_outputs_record_one_row_per_citation_idempotently(): void
    {
        $id = (string) Str::uuid7();
        $answer = ['paragraphs' => [['text' => 'نعم بالقدر اللازم.', 'citations' => [$this->cite()]]]];
        CitationRecorder::consult($id, $answer);
        CitationRecorder::consult($id, $answer);

        $this->assertDatabaseCount('citations', 1);
        $row = Citation::query()->firstOrFail();
        $this->assertSame(['consult', 'paragraphs[0]', 'knowledge', true], [$row->route, $row->item_path, $row->source_kind, $row->verified]);
        $this->assertStringNotContainsString('بالقدر', (string) DB::table('citations')->value('claim'), 'claims are stored encrypted');
    }

    public function test_the_sample_is_stratified_and_holds_only_verified_regulation_citations(): void
    {
        $this->seedCitations(12);
        $this->asLawyer();

        $rows = $this->getJson('/api/v1/lawyer/citation-samples')->assertOk()->json('data');

        $this->assertCount(30, $rows);
        $this->assertSame(['analysis' => 10, 'consult' => 10, 'draft' => 10], collect($rows)->countBy('route')->sortKeys()->all());
        $this->assertNotContains('D1', collect($rows)->pluck('quote')->all());
        $this->assertSame(0, Citation::query()->whereNotNull('sampled_at')->where(fn ($q) => $q->where('verified', false)->orWhere('source_kind', 'case_document'))->count());
        $this->assertSame($this->case->code, collect($rows)->firstWhere('route', 'analysis')['case_code']);
        $this->assertStringContainsString('المادة 21', $rows[0]['source_label']);
        $this->assertStringContainsString('يحافظ على الأسرار', $rows[0]['source_text']);

        // The same sample comes back until most of it is judged.
        $this->assertSame(collect($rows)->pluck('id')->sort()->values()->all(), collect($this->getJson('/api/v1/lawyer/citation-samples')->json('data'))->pluck('id')->sort()->values()->all());
    }

    public function test_verdicts_feed_the_accuracy_kpi(): void
    {
        $this->seedCitations(2);
        $lawyer = $this->asLawyer();
        $rows = $this->getJson('/api/v1/lawyer/citation-samples')->json('data');

        foreach (array_slice($rows, 0, 4) as $i => $row) {
            $this->postJson("/api/v1/lawyer/citation-samples/{$row['id']}/verdict", ['verdict' => $i === 0 ? 'incorrect' : 'correct', 'note' => $i === 0 ? 'الاقتباس لا يدعم الادعاء' : null])
                ->assertOk()->assertJsonPath('data.verdict', $i === 0 ? 'incorrect' : 'correct');
        }

        $this->getJson('/api/v1/lawyer/citation-samples')->assertJsonPath('meta.reviewed', 4)->assertJsonPath('meta.accuracy', 0.75);
        $this->assertDatabaseHas('citations', ['lawyer_verdict' => 'incorrect', 'reviewed_by' => $lawyer->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'citation.judged']);

        $unsampled = Citation::query()->whereNull('sampled_at')->value('id');
        if ($unsampled !== null) {
            $this->postJson("/api/v1/lawyer/citation-samples/{$unsampled}/verdict", ['verdict' => 'correct'])->assertNotFound();
        }

        $admin = User::factory()->create(['phone_e164' => null, 'email' => 'admin@dentlegal.test', 'password' => 'password-123', 'two_factor_confirmed_at' => now()]);
        $admin->assignRole('admin');
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->asLawyer($admin);
        $this->getJson('/api/v1/admin/stats/overview')->assertJsonPath('data.citation_accuracy_lawyer', 0.75)->assertJsonPath('data.citations_judged', 4);
    }

    public function test_only_lawyers_review_samples_and_backfill_records_existing_output(): void
    {
        Sanctum::actingAs(User::factory()->dentist()->create(), ['dentist']);
        $this->getJson('/api/v1/lawyer/citation-samples')->assertForbidden();

        $this->artisan('citations:backfill')->assertSuccessful();
    }
}
