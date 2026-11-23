<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retention (PROJECT1_PLAN.md §9.7, S9.2): deleted accounts are purged to a tombstone (purged_at); audit rows older than
 * two years may be deleted by the retention job, while newer rows stay append-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('purged_at')->nullable();
        });

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION audit_logs_append_only() RETURNS trigger AS $$
            BEGIN
                -- Retention: only rows past the two-year period may go, and only by DELETE (never UPDATE or TRUNCATE).
                IF TG_OP = 'DELETE' AND OLD.occurred_at < now() - interval '2 years' THEN
                    RETURN OLD;
                END IF;
                RAISE EXCEPTION 'audit_logs is append-only' USING ERRCODE = 'insufficient_privilege';
            END;
            $$ LANGUAGE plpgsql;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION audit_logs_append_only() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'audit_logs is append-only' USING ERRCODE = 'insufficient_privilege';
            END;
            $$ LANGUAGE plpgsql;
        SQL);
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('purged_at'));
    }
};
