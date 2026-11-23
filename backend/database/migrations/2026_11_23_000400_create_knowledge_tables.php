<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Knowledge base for the RAG (PROJECT1_PLAN.md §3.2, §3.3, §5.3). Laravel owns the schema and the version
 * lifecycle; ai-svc writes chunks and embeddings during ingestion and reads active chunks for retrieval.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        Schema::create('knowledge_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 40)->unique(); // HPPL, DENTAL_SVC_REG, GP_JOB
            $table->string('title_ar', 200);
            $table->string('issuer', 160)->nullable();
            $table->string('type', 20);
            $table->timestamps();
        });
        DB::statement("ALTER TABLE knowledge_sources ADD CONSTRAINT ks_type_check CHECK (type IN ('law','regulation','job_description','guideline'))");

        Schema::create('knowledge_source_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('source_id')->constrained('knowledge_sources')->restrictOnDelete();
            $table->string('version_label', 60);
            $table->string('edition', 120)->nullable();
            $table->date('effective_from')->nullable();
            $table->string('file_key', 255)->nullable();
            $table->string('sha256', 64)->nullable();
            $table->string('extraction_method', 20)->nullable();
            $table->string('status', 12)->default('uploaded');
            $table->unsignedInteger('chunk_count')->default(0);
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->unique(['source_id', 'version_label']);
        });
        DB::statement("ALTER TABLE knowledge_source_versions ADD CONSTRAINT ksv_status_check CHECK (status IN ('uploaded','processing','review','active','archived','failed'))");
        DB::statement("ALTER TABLE knowledge_source_versions ADD CONSTRAINT ksv_method_check CHECK (extraction_method IS NULL OR extraction_method IN ('text_layer','ocr','llm_transcription'))");
        // Exactly one active version per source.
        DB::statement("CREATE UNIQUE INDEX ksv_one_active_per_source ON knowledge_source_versions (source_id) WHERE status = 'active'");

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('version_id')->constrained('knowledge_source_versions')->restrictOnDelete();
            $table->foreignUuid('source_id')->constrained('knowledge_sources')->restrictOnDelete();
            $table->string('status', 12); // denormalised from the version, for partial indexes
            $table->string('chunk_key', 80);
            $table->unsignedInteger('ordinal');
            $table->string('chunk_type', 16);
            $table->unsignedSmallInteger('article_no')->nullable();
            $table->string('paragraph_ref', 20)->nullable();
            $table->unsignedSmallInteger('page_from')->nullable();
            $table->unsignedSmallInteger('page_to')->nullable();
            $table->text('display_text');
            $table->text('search_text');
            $table->unsignedSmallInteger('token_count');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['version_id', 'chunk_key']);
            $table->unique(['version_id', 'ordinal']);
        });
        DB::statement('ALTER TABLE knowledge_chunks ADD COLUMN heading_path text[] NOT NULL DEFAULT \'{}\'');
        DB::statement('ALTER TABLE knowledge_chunks ADD COLUMN embedding vector(1024)');
        DB::statement("ALTER TABLE knowledge_chunks ADD COLUMN tsv tsvector GENERATED ALWAYS AS (to_tsvector('arabic'::regconfig, search_text)) STORED");
        DB::statement("ALTER TABLE knowledge_chunks ADD CONSTRAINT kc_status_check CHECK (status IN ('uploaded','processing','review','active','archived','failed'))");
        DB::statement("ALTER TABLE knowledge_chunks ADD CONSTRAINT kc_type_check CHECK (chunk_type IN ('article','regulation','definition','section','form'))");
        // An active chunk must be embedded; retrieval relies on it.
        DB::statement("ALTER TABLE knowledge_chunks ADD CONSTRAINT kc_active_embedded CHECK (status <> 'active' OR embedding IS NOT NULL)");

        // Retrieval indexes cover active chunks only, so archived versions are never retrieved (§3.3).
        DB::statement("CREATE INDEX kc_embedding_hnsw ON knowledge_chunks USING hnsw (embedding vector_cosine_ops) WITH (m = 16, ef_construction = 64) WHERE status = 'active'");
        DB::statement("CREATE INDEX kc_tsv_gin ON knowledge_chunks USING gin (tsv) WHERE status = 'active'");
        DB::statement("CREATE INDEX kc_trgm_gin ON knowledge_chunks USING gin (search_text gin_trgm_ops) WHERE status = 'active'");
        DB::statement("CREATE INDEX kc_article ON knowledge_chunks (source_id, article_no, paragraph_ref) WHERE status = 'active'");
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('knowledge_source_versions');
        Schema::dropIfExists('knowledge_sources');
    }
};
