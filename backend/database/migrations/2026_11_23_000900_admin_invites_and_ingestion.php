<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Admin API (PROJECT1_PLAN.md S7.1, S1.5): invite details and resends; uploaded source files and ingestion reports. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table): void {
            $table->jsonb('meta')->nullable();            // dentist: specialty, city · lawyer: license_no, firm_name
            $table->unsignedSmallInteger('sent_count')->default(0);
            $table->timestamp('last_sent_at')->nullable();
        });
        Schema::table('knowledge_source_versions', function (Blueprint $table): void {
            $table->string('file_name', 200)->nullable();
            $table->string('requested_method', 20)->default('auto'); // auto | text_layer | ocr | llm_transcription
            $table->jsonb('ingest_report')->nullable();               // pages, methods, warnings, preview (ai-svc)
        });
    }

    public function down(): void
    {
        Schema::table('invitations', fn (Blueprint $t) => $t->dropColumn(['meta', 'sent_count', 'last_sent_at']));
        Schema::table('knowledge_source_versions', fn (Blueprint $t) => $t->dropColumn(['file_name', 'requested_method', 'ingest_report']));
    }
};
