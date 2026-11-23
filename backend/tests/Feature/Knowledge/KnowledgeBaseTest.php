<?php

namespace Tests\Feature\Knowledge;

use App\Modules\Identity\Models\User;
use App\Modules\Knowledge\Actions\PublishSourceVersion;
use App\Modules\Knowledge\Enums\VersionStatus;
use App\Modules\Knowledge\Models\KnowledgeSource;
use App\Modules\Knowledge\Models\KnowledgeSourceVersion;
use App\Support\Api\ApiException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    private KnowledgeSource $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->source = KnowledgeSource::query()->create(['code' => 'HPPL', 'title_ar' => 'نظام مزاولة المهن الصحية', 'type' => 'law']);
    }

    /** Inserts chunks the way ai-svc does during ingestion. Synthetic text, deterministic fake embeddings. */
    private function versionWithChunks(string $label, VersionStatus $status, int $chunks = 2): KnowledgeSourceVersion
    {
        $version = KnowledgeSourceVersion::query()->create([
            'source_id' => $this->source->id, 'version_label' => $label, 'edition' => 'الإصدار '.$label,
            'status' => $status, 'chunk_count' => $chunks,
        ]);
        for ($i = 1; $i <= $chunks; $i++) {
            $vector = '['.implode(',', array_fill(0, 1024, round(1 / 32, 6))).']';
            DB::insert(
                'INSERT INTO knowledge_chunks (version_id, source_id, status, chunk_key, ordinal, chunk_type, article_no, display_text, search_text, token_count, embedding)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?::vector)',
                [$version->id, $this->source->id, $status->value, "HPPL:{$label}:A{$i}", $i, 'article', $i,
                    "المادة {$i}: نص تجريبي عن التزامات الممارس الصحي.", "الماده {$i} نص تجريبي عن التزامات الممارس الصحي", 20, $vector],
            );
        }

        return $version;
    }

    private function admin(): User
    {
        return User::factory()->create(['phone_e164' => null, 'email' => 'admin@dentlegal.test']);
    }

    public function test_publishing_archives_the_previous_version_atomically(): void
    {
        $old = $this->versionWithChunks('1433', VersionStatus::Active);
        $new = $this->versionWithChunks('1440', VersionStatus::Review, 3);

        app(PublishSourceVersion::class)($new, $this->admin());

        $this->assertSame(VersionStatus::Archived, $old->refresh()->status);
        $this->assertSame(VersionStatus::Active, $new->refresh()->status);
        $this->assertSame(3, DB::table('knowledge_chunks')->where('status', 'active')->count());
        $this->assertSame(2, DB::table('knowledge_chunks')->where('status', 'archived')->where('version_id', $old->id)->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'knowledge.version_published', 'subject_id' => $new->id]);
    }

    public function test_only_reviewed_versions_can_be_published(): void
    {
        $version = $this->versionWithChunks('1440', VersionStatus::Processing);

        $this->expectException(ApiException::class);
        app(PublishSourceVersion::class)($version, $this->admin());
    }

    public function test_database_allows_only_one_active_version_per_source(): void
    {
        $this->versionWithChunks('1433', VersionStatus::Active);

        $this->expectException(QueryException::class);
        $this->versionWithChunks('1440', VersionStatus::Active);
    }

    public function test_active_chunks_must_have_an_embedding(): void
    {
        $version = KnowledgeSourceVersion::query()->create(['source_id' => $this->source->id, 'version_label' => 'x', 'status' => VersionStatus::Review]);

        $this->expectException(QueryException::class);
        DB::insert("INSERT INTO knowledge_chunks (version_id, source_id, status, chunk_key, ordinal, chunk_type, display_text, search_text, token_count) VALUES (?, ?, 'active', 'k', 1, 'article', 't', 't', 1)", [$version->id, $this->source->id]);
    }

    public function test_arabic_full_text_and_vector_search_work_on_active_chunks(): void
    {
        $this->versionWithChunks('1440', VersionStatus::Active);

        $fts = DB::select("SELECT id FROM knowledge_chunks WHERE status = 'active' AND tsv @@ plainto_tsquery('arabic', 'التزامات الممارس')");
        $this->assertCount(2, $fts);

        $query = '['.implode(',', array_fill(0, 1024, round(1 / 32, 6))).']';
        $nearest = DB::select("SELECT id, 1 - (embedding <=> ?::vector) AS score FROM knowledge_chunks WHERE status = 'active' ORDER BY embedding <=> ?::vector LIMIT 1", [$query, $query]);
        $this->assertEqualsWithDelta(1.0, (float) $nearest[0]->score, 0.0001);
    }

    public function test_dentist_reads_a_cited_chunk_including_archived_ones(): void
    {
        $old = $this->versionWithChunks('1433', VersionStatus::Archived, 1);
        $chunkId = DB::table('knowledge_chunks')->where('version_id', $old->id)->value('id');
        $this->actingAsDentist();

        $this->getJson("/api/v1/knowledge/chunks/{$chunkId}")->assertOk()
            ->assertJsonPath('data.label', 'المادة 1')
            ->assertJsonPath('data.law', 'نظام مزاولة المهن الصحية')
            ->assertJsonPath('data.is_current', false)
            ->assertJsonMissingPath('data.embedding');
    }
}
