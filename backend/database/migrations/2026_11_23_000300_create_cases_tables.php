<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per year; incremented atomically to give DL-YYYY-NNNN without gaps from races.
        Schema::create('case_code_counters', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_no');
        });

        Schema::create('cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique();
            $table->foreignUuid('dentist_id')->constrained('users')->restrictOnDelete();
            $table->string('case_type', 20);
            $table->string('authority', 200);
            $table->string('complaint_no', 60);
            $table->date('received_on');
            $table->string('title', 160);
            $table->string('procedure', 200);
            $table->date('procedure_date');
            $table->string('patient_code', 30);
            $table->string('claim_type', 16);
            $table->text('facts_masked'); // encrypted (application level)
            $table->jsonb('facts_entity_counts')->default('{}');
            $table->string('status', 16)->default('draft');
            $table->string('failure_code', 40)->nullable();
            $table->timestamp('submitted_for_review_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['dentist_id', 'status']);
            $table->index(['status', 'submitted_for_review_at']);
            $table->index(['dentist_id', 'updated_at']);
        });
        DB::statement("ALTER TABLE cases ADD CONSTRAINT cases_status_check CHECK (status IN ('draft','analyzing','ai_ready','lawyer_review','approved','failed'))");
        DB::statement("ALTER TABLE cases ADD CONSTRAINT cases_type_check CHECK (case_type IN ('complaint','official_notice','sharia_board','preventive'))");
        DB::statement("ALTER TABLE cases ADD CONSTRAINT cases_claim_check CHECK (claim_type IN ('compensation','disciplinary','unknown'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
        Schema::dropIfExists('case_code_counters');
    }
};
