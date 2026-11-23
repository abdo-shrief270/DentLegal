<?php

namespace Tests\Feature\Admin;

use App\Modules\Cases\Models\DentalCase;
use App\Modules\Identity\Http\Controllers\Dashboard\DashboardAuthController;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Sms\SmsGateway;
use App\Modules\Knowledge\Enums\VersionStatus;
use App\Modules\Knowledge\Models\KnowledgeSource;
use App\Modules\Knowledge\Models\KnowledgeSourceVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mailer\SentMessage;
use Tests\TestCase;

/** Admin API: users and invites (S7.1), knowledge-base lifecycle (S1.5). Synthetic people and public-regulation text. */
class AdminApiTest extends TestCase
{
    /** @var list<array{0: string, 1: string}> */
    private array $sms = [];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['Origin' => 'http://localhost', 'Referer' => 'http://localhost/']);
        $this->app->instance(SmsGateway::class, new class($this->sms) implements SmsGateway
        {
            /** @param list<array{0: string, 1: string}> $sent */
            public function __construct(private array &$sent) {}

            public function send(string $phoneE164, string $message): void
            {
                $this->sent[] = [$phoneE164, $message];
            }
        });
        $this->admin = User::factory()->create(['phone_e164' => null, 'email' => 'admin@dentlegal.test', 'password' => 'password-123']);
        $this->admin->assignRole('admin');
        $this->signIn($this->admin);
    }

    private function signIn(User $user): void
    {
        $user->refresh(); // a real request loads the account fresh
        $user->forceFill(['two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'two_factor_confirmed_at' => now()])->save();
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->actingAs($user, 'web')->withSession([DashboardAuthController::PASSED => time()]);
    }

    private function lastMailBody(): string
    {
        /** @var list<SentMessage> $messages */
        $messages = app('mailer')->getSymfonyTransport()->messages()->all();

        return (string) end($messages)?->getOriginalMessage()->getTextBody();
    }

    public function test_dentist_invites_sms_duplicates_and_list(): void
    {
        $existing = User::factory()->dentist()->create();
        DentalCase::factory()->for($existing, 'dentist')->count(2)->create();

        $this->postJson('/api/v1/admin/dentists', ['name' => 'د. سلمى الحربي', 'phone' => '0551112233', 'specialty' => 'تقويم', 'city' => 'جدة'])
            ->assertCreated()->assertJsonPath('data.status', 'invited')->assertJsonPath('data.phone', '+966 55 *** 2233');
        $this->assertSame('+966551112233', $this->sms[0][0]);
        $this->assertStringContainsString('حمّل التطبيق', $this->sms[0][1]);

        $this->postJson('/api/v1/admin/dentists', ['name' => 'مكرر', 'phone' => '+966551112233'])->assertStatus(409)->assertJsonPath('error.code', 'already_invited');
        $this->postJson('/api/v1/admin/dentists', ['name' => 'رقم خاطئ', 'phone' => '123'])->assertUnprocessable();

        $rows = $this->getJson('/api/v1/admin/dentists')->assertOk()->json('data');
        $this->assertSame(['invited', 'active'], array_column($rows, 'status'));
        $this->assertSame(2, $rows[1]['cases']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'invitation.created']);
    }

    public function test_lawyer_invite_link_works_and_a_resend_replaces_it(): void
    {
        $this->postJson('/api/v1/admin/lawyers', ['name' => 'أ. هند القحطاني', 'email' => 'hind@dentlegal.test', 'license_no' => '44/1290', 'firm' => 'مكتب القحطاني'])->assertCreated();
        preg_match('#/invite/([A-Za-z0-9]{48})#', $this->lastMailBody(), $first);
        $id = $this->getJson('/api/v1/admin/lawyers')->assertOk()->assertJsonPath('data.0.status', 'invited')->json('data.0.id');

        $this->postJson("/api/v1/admin/users/{$id}/resend-invite")->assertStatus(429);
        $this->travel(2)->minutes();
        $this->postJson("/api/v1/admin/users/{$id}/resend-invite")->assertOk();
        preg_match('#/invite/([A-Za-z0-9]{48})#', $this->lastMailBody(), $second);
        $this->assertNotSame($first[1], $second[1]);

        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->getJson("/invitations/{$first[1]}")->assertNotFound();
        $this->postJson("/invitations/{$second[1]}/accept", ['password' => 'Correct-horse-42'])->assertCreated();
        $this->assertDatabaseHas('lawyer_profiles', ['license_no' => '44/1290', 'firm_name' => 'مكتب القحطاني']);

        $this->signIn($this->admin);
        $this->getJson('/api/v1/admin/lawyers')->assertOk()->assertJsonPath('data.0.status', 'active')->assertJsonPath('data.0.license_no', '44/1290');
    }

    public function test_deactivation_revokes_tokens_and_blocks_the_dashboard(): void
    {
        $dentist = User::factory()->dentist()->create();
        $dentist->createToken('app', ['dentist']);
        $lawyer = User::factory()->lawyer()->create();

        $this->postJson("/api/v1/admin/users/{$dentist->id}/deactivate")->assertOk()->assertJsonPath('data.status', 'disabled');
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->postJson("/api/v1/admin/users/{$this->admin->id}/deactivate")->assertForbidden()->assertJsonPath('error.code', 'cannot_change_self');

        $this->postJson("/api/v1/admin/users/{$lawyer->id}/deactivate")->assertOk();
        $this->signIn($lawyer);
        $this->getJson('/api/v1/lawyer/reviews')->assertForbidden()->assertJsonPath('error.code', 'account_disabled');

        $this->signIn($this->admin);
        $this->postJson("/api/v1/admin/users/{$lawyer->id}/activate")->assertOk()->assertJsonPath('data.status', 'active');
    }

    public function test_upload_ingests_into_review_then_publish_and_archive(): void
    {
        Storage::fake('knowledge');
        $source = KnowledgeSource::query()->create(['code' => 'HPPL', 'title_ar' => 'نظام مزاولة المهن الصحية', 'type' => 'law']);
        Http::fake(['ai-svc.test/v1/kb/ingest' => function () {
            // ai-svc writes the chunks itself; the fake does the same so the version can be published.
            $version = KnowledgeSourceVersion::query()->latest('created_at')->firstOrFail();
            $vector = '['.implode(',', array_fill(0, 1024, round(1 / 32, 6))).']';
            DB::insert("INSERT INTO knowledge_chunks (version_id, source_id, status, chunk_key, ordinal, chunk_type, article_no, display_text, search_text, token_count, embedding)
                VALUES (?, ?, 'review', 'HPPL:1440:A1', 1, 'article', 1, 'المادة 1: نص تجريبي.', 'الماده 1 نص تجريبي', 10, ?::vector)", [$version->id, $version->source_id, $vector]);

            return Http::response(['chunk_count' => 1, 'chunks_by_type' => ['article' => 1], 'extraction_method' => 'text_layer',
                'pages' => [['page_no' => 1, 'method' => 'text_layer_repaired', 'confidence' => 1.0]], 'warnings' => ['missing_articles' => [], 'low_confidence_pages' => []], 'preview' => []]);
        }]);

        $this->postJson("/api/v1/admin/knowledge/sources/{$source->id}/versions", [
            'file' => UploadedFile::fake()->createWithContent('law.pdf', "%PDF-1.7\nsynthetic"), 'edition' => 'الإصدار الثالث 1440هـ', 'extraction_method' => 'auto',
        ])->assertStatus(202);

        $version = KnowledgeSourceVersion::query()->firstOrFail();
        $this->assertSame(VersionStatus::Review, $version->status); // the queue is sync in tests
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/v1/kb/ingest'));
        $this->getJson("/api/v1/admin/knowledge/versions/{$version->id}")->assertOk()->assertJsonPath('data.version.status', 'review')->assertJsonPath('data.version.extraction_method', 'text_layer');
        $this->getJson("/api/v1/admin/knowledge/versions/{$version->id}/chunks")->assertOk()->assertJsonPath('data.0.label', 'المادة 1');

        $this->postJson("/api/v1/admin/knowledge/versions/{$version->id}/publish")->assertOk()->assertJsonPath('data.status', 'active');
        $this->postJson("/api/v1/admin/knowledge/versions/{$version->id}/reindex")->assertStatus(409);
        $this->postJson("/api/v1/admin/knowledge/versions/{$version->id}/archive")->assertOk()->assertJsonPath('data.status', 'archived');
        $this->assertSame(0, DB::table('knowledge_chunks')->where('status', 'active')->count());

        $this->getJson('/api/v1/admin/knowledge/sources')->assertOk()->assertJsonPath('data.0.versions.0.status', 'archived');
    }

    public function test_new_source_failed_ingestion_and_reindex(): void
    {
        Storage::fake('knowledge');
        $fail = true;
        Http::fake(['ai-svc.test/v1/kb/ingest' => function () use (&$fail) {
            return $fail ? Http::response(['error' => ['code' => 'no_content', 'message' => 'no text', 'retryable' => false]], 422)
                : Http::response(['chunk_count' => 0, 'extraction_method' => 'ocr', 'warnings' => []]);
        }]);

        $this->postJson('/api/v1/admin/knowledge/sources/new/versions', ['file' => UploadedFile::fake()->createWithContent('x.png', 'not a pdf'), 'edition' => '2026', 'extraction_method' => 'auto', 'new_source_title' => 'دليل'])
            ->assertStatus(415);
        $id = $this->postJson('/api/v1/admin/knowledge/sources/new/versions', [
            'file' => UploadedFile::fake()->createWithContent('guide.pdf', "%PDF-1.4\nsynthetic"), 'edition' => '2026', 'extraction_method' => 'ocr',
            'new_source_title' => 'دليل ممارسة طب الأسنان', 'new_source_type' => 'guideline',
        ])->assertStatus(202)->json('data.id');

        $this->getJson("/api/v1/admin/knowledge/versions/{$id}")->assertJsonPath('data.version.status', 'failed')->assertJsonPath('data.source.type', 'guideline');
        $this->assertStringStartsWith('no_content', (string) KnowledgeSourceVersion::query()->findOrFail($id)->error);

        $fail = false;
        $this->postJson("/api/v1/admin/knowledge/versions/{$id}/reindex", ['extraction_method' => 'llm_transcription'])->assertStatus(202);
        $this->assertSame(VersionStatus::Review, KnowledgeSourceVersion::query()->findOrFail($id)->status);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/v1/kb/ingest') && str_contains((string) $r->body(), 'llm_transcription'));
    }

    public function test_only_admins(): void
    {
        $this->signIn(User::factory()->lawyer()->create());

        $this->getJson('/api/v1/admin/dentists')->assertForbidden();
        $this->getJson('/api/v1/admin/knowledge/sources')->assertForbidden();
    }
}
