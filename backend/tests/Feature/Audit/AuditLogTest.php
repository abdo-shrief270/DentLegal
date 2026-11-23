<?php

namespace Tests\Feature\Audit;

use App\Modules\Audit\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    public function test_audit_log_is_append_only_at_database_level(): void
    {
        app(AuditLogger::class)->record('test.event', metadata: ['n' => 1]);
        $id = DB::table('audit_logs')->value('id');

        try {
            DB::table('audit_logs')->where('id', $id)->update(['action' => 'tampered']);
            $this->fail('UPDATE should be rejected');
        } catch (QueryException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }
    }

    public function test_deletes_are_rejected(): void
    {
        app(AuditLogger::class)->record('test.event');

        $this->expectException(QueryException::class);
        DB::table('audit_logs')->delete();
    }
}
