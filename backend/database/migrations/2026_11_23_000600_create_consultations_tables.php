<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Consultant (PROJECT1_PLAN.md §3, §4.2): one conversation per thread, a question and its answer per turn. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120);
            $table->jsonb('placeholder_state')->nullable(); // masking numbering for this thread: keyed hashes, never values
            $table->string('idempotency_key', 100)->nullable();
            $table->timestampTz('last_message_at');
            $table->timestampsTz();

            $table->index(['user_id', 'last_message_at']);
            $table->unique(['user_id', 'idempotency_key']);
        });

        Schema::create('consultation_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('consultation_id')->constrained('consultations')->cascadeOnDelete();
            $table->string('role', 10);
            $table->uuid('reply_to_id')->nullable(); // the question this answers; FK added below (self-reference)
            $table->text('text_masked')->nullable(); // encrypted; the question, masked before storage
            $table->text('answer_json')->nullable(); // encrypted array: paragraphs, verified citations, sources
            $table->string('status', 12)->nullable(); // answers only
            $table->string('error_code', 40)->nullable();
            $table->string('idempotency_key', 100)->nullable();
            $table->foreignId('ai_run_id')->nullable()->constrained('ai_runs')->nullOnDelete();
            $table->timestampTz('answered_at')->nullable();
            $table->timestampsTz();

            $table->index(['consultation_id', 'created_at']);
            $table->unique(['consultation_id', 'idempotency_key']);
        });

        Schema::table('consultation_messages', function (Blueprint $table): void {
            $table->foreign('reply_to_id')->references('id')->on('consultation_messages')->cascadeOnDelete();
            $table->unique('reply_to_id');
        });
        DB::statement("ALTER TABLE consultation_messages ADD CONSTRAINT consultation_messages_role_check CHECK (role IN ('user', 'assistant'))");
        DB::statement("ALTER TABLE consultation_messages ADD CONSTRAINT consultation_messages_status_check CHECK (status IN ('pending', 'answered', 'refused', 'failed'))");
        // One open question per thread: a second question waits for the first answer.
        DB::statement("CREATE UNIQUE INDEX consultation_messages_one_pending ON consultation_messages (consultation_id) WHERE status = 'pending'");
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_messages');
        Schema::dropIfExists('consultations');
    }
};
