<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Ratings (S6.1), general feedback (S6.2) and the nightly usage roll-up (S6.3), PROJECT1_PLAN.md §3. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('target_type', 24);
            $table->uuid('target_id');
            $table->boolean('useful');
            $table->text('comment')->nullable(); // encrypted: free text may describe the case
            $table->timestamps();
            $table->unique(['user_id', 'target_type', 'target_id']); // the last rating wins
            $table->index(['target_type', 'created_at']);
        });
        DB::statement("ALTER TABLE ratings ADD CONSTRAINT ratings_target_check CHECK (target_type IN ('consultation_message','analysis','draft_version'))");

        Schema::create('feedback', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 12);
            $table->text('message'); // encrypted
            $table->string('screen', 60)->nullable();
            $table->string('app_version', 40)->nullable();
            $table->string('platform', 10)->nullable();
            $table->string('status', 8)->default('new');
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
        DB::statement("ALTER TABLE feedback ADD CONSTRAINT feedback_category_check CHECK (category IN ('bug','suggestion','other'))");
        DB::statement("ALTER TABLE feedback ADD CONSTRAINT feedback_status_check CHECK (status IN ('new','seen','done'))");

        Schema::create('daily_usage_stats', function (Blueprint $table): void {
            $table->date('day');
            $table->string('metric', 40);
            $table->decimal('value', 14, 4);
            $table->primary(['day', 'metric']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_usage_stats');
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('ratings');
    }
};
