<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Case documents, pipeline runs, analyses, drafts and AI run logs (PROJECT1_PLAN.md §3.2). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('cases')->cascadeOnDelete();
            $table->string('kind', 24);
            $table->string('original_name', 200);
            $table->string('mime', 40);
            $table->unsignedInteger('size_bytes');
            $table->string('storage_key', 200);
            $table->text('wrapped_key'); // per-file data key, encrypted with the document master key
            $table->string('sha256', 64);
            $table->unsignedSmallInteger('page_count')->nullable();
            $table->string('ocr_status', 12)->default('pending');
            $table->string('ocr_method', 24)->nullable();
            $table->decimal('ocr_confidence', 4, 3)->nullable();
            $table->boolean('low_quality')->default(false);
            $table->timestamps();
            $table->index(['case_id', 'created_at']);
        });
        DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_kind_check CHECK (kind IN ('complaint_letter','medical_record','consent_form','radiology','referral','insurance','other'))");
        DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_ocr_check CHECK (ocr_status IN ('pending','done','failed'))");

        Schema::create('document_pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedSmallInteger('page_no');
            $table->text('text_masked'); // encrypted; raw OCR text is never stored
            $table->decimal('confidence', 4, 3);
            $table->string('method', 24);
            $table->jsonb('masked_entity_counts')->default('{}');
            $table->unique(['document_id', 'page_no']);
        });

        Schema::create('pipeline_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('cases')->cascadeOnDelete();
            $table->string('type', 24)->default('case_analysis');
            $table->string('status', 12)->default('queued');
            $table->string('current_step', 16)->nullable();
            $table->jsonb('steps');
            $table->jsonb('placeholder_state')->nullable(); // keyed hashes only, never masked values
            $table->string('idempotency_key', 100)->nullable();
            $table->string('error_code', 40)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->unique(['case_id', 'idempotency_key']);
            $table->index(['case_id', 'created_at']);
        });
        DB::statement("ALTER TABLE pipeline_jobs ADD CONSTRAINT pipeline_jobs_status_check CHECK (status IN ('queued','running','succeeded','failed'))");
        // At most one pipeline in flight per case.
        DB::statement("CREATE UNIQUE INDEX pipeline_jobs_one_active ON pipeline_jobs (case_id) WHERE status IN ('queued','running')");

        Schema::create('ai_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_type', 24);
            $table->string('subject_type', 32)->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->string('status', 24);
            $table->string('prompt_version', 40)->nullable();
            $table->string('schema_version', 16)->nullable();
            $table->string('model_requested', 60)->nullable();
            $table->string('model_served', 60)->nullable();
            $table->string('effort', 12)->nullable();
            $table->string('inference_geo', 12)->nullable();
            $table->boolean('fallback_used')->default(false);
            $table->string('stop_reason', 24)->nullable();
            $table->string('refusal_category', 40)->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cache_read_tokens')->default(0);
            $table->unsignedInteger('cache_write_tokens')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->decimal('citation_validity', 5, 4)->nullable();
            $table->boolean('repaired')->default(false);
            $table->jsonb('retrieval_params')->nullable();
            $table->string('error_code', 40)->nullable();
            $table->string('anthropic_request_id', 80)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['run_type', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('ai_run_sources', function (Blueprint $table) {
            $table->foreignId('ai_run_id')->constrained('ai_runs')->cascadeOnDelete();
            $table->unsignedBigInteger('chunk_id');
            $table->string('local_ref', 8)->nullable();
            $table->unsignedSmallInteger('dense_rank')->nullable();
            $table->unsignedSmallInteger('sparse_rank')->nullable();
            $table->decimal('rerank_score', 6, 4)->nullable();
            $table->boolean('used_in_prompt')->default(true);
            $table->primary(['ai_run_id', 'chunk_id']);
        });

        Schema::create('analyses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignUuid('pipeline_job_id')->nullable()->constrained('pipeline_jobs')->nullOnDelete();
            $table->foreignId('ai_run_id')->nullable()->constrained('ai_runs')->nullOnDelete();
            $table->unsignedTinyInteger('position_strength');
            $table->string('risk_level', 8);
            $table->text('result_json'); // encrypted array: masked analysis with verified citations
            $table->jsonb('source_chunk_ids');
            $table->boolean('sources_sufficient');
            $table->boolean('needs_attention')->default(false);
            $table->boolean('is_current')->default(true);
            $table->timestamps();
        });
        DB::statement('CREATE UNIQUE INDEX analyses_one_current ON analyses (case_id) WHERE is_current');

        Schema::create('drafts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->unique()->constrained('cases')->cascadeOnDelete(); // one draft per case in P1
            $table->foreignUuid('analysis_id')->nullable()->constrained('analyses')->nullOnDelete();
            $table->string('status', 16)->default('ai_generated');
            $table->uuid('current_version_id')->nullable();
            $table->uuid('approved_version_id')->nullable();
            $table->foreignUuid('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE drafts ADD CONSTRAINT drafts_status_check CHECK (status IN ('ai_generated','in_review','approved'))");

        Schema::create('draft_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('draft_id')->constrained('drafts')->cascadeOnDelete();
            $table->unsignedSmallInteger('version_no');
            $table->string('author_type', 8);
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('content_json'); // encrypted array
            $table->string('change_note', 300)->nullable();
            $table->foreignId('ai_run_id')->nullable()->constrained('ai_runs')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['draft_id', 'version_no']);
        });
        DB::statement("ALTER TABLE draft_versions ADD CONSTRAINT draft_versions_author_check CHECK (author_type IN ('ai','lawyer'))");
    }

    public function down(): void
    {
        foreach (['draft_versions', 'drafts', 'analyses', 'ai_run_sources', 'ai_runs', 'pipeline_jobs', 'document_pages', 'documents'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
