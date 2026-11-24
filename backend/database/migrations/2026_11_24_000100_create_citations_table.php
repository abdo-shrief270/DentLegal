<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One row per citation in AI output (PROJECT1_PLAN.md §3, §5.9 step 6): what the verifier said, and the lawyer's verdict
 * when the citation is drawn into the accuracy sample (S4.6, pilot KPI ≥ 95%).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citations', function (Blueprint $table): void {
            $table->id();
            $table->string('citable_type', 24);
            $table->uuid('citable_id');
            $table->string('route', 10);
            $table->uuid('case_id')->nullable();
            $table->string('item_path', 60);
            $table->string('source_kind', 16);
            $table->string('ref', 8);
            $table->unsignedBigInteger('chunk_id')->nullable();
            $table->uuid('document_page_id')->nullable();
            $table->text('claim');  // encrypted: the masked sentence that cites
            $table->text('quote');  // encrypted
            $table->boolean('verified');
            $table->jsonb('verification')->nullable();
            $table->timestamp('sampled_at')->nullable();
            $table->string('lawyer_verdict', 10)->nullable();
            $table->text('lawyer_note')->nullable(); // encrypted
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['citable_type', 'citable_id', 'item_path', 'ref']);
            $table->index(['citable_type', 'citable_id']);
            $table->index('chunk_id');
            $table->index(['verified', 'lawyer_verdict']);
            $table->index('sampled_at');
        });
        DB::statement("ALTER TABLE citations ADD CONSTRAINT citations_route_check CHECK (route IN ('consult','analysis','draft'))");
        DB::statement("ALTER TABLE citations ADD CONSTRAINT citations_verdict_check CHECK (lawyer_verdict IS NULL OR lawyer_verdict IN ('correct','incorrect'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('citations');
    }
};
