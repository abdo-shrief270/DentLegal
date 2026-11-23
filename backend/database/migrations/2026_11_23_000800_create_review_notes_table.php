<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Internal review notes (PROJECT1_PLAN.md §4.2): lawyers only, never shown to the dentist. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_notes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('draft_id')->constrained('drafts')->cascadeOnDelete();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('text'); // encrypted
            $table->timestamp('created_at')->useCurrent();
            $table->index(['draft_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_notes');
    }
};
