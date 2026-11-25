<?php

namespace Tests\Feature\Admin;

use App\Modules\Cases\Models\DentalCase;
use App\Modules\Identity\Http\Controllers\Dashboard\DashboardAuthController;
use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Models\AiRun;
use App\Modules\Pipeline\Models\Analysis;
use App\Modules\Pipeline\Support\AiRunRecorder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** «سجل تشغيل الذكاء الاصطناعي» (S7.4): read-only ai_runs with filters and a summary. Synthetic runs only. */
class AdminAiRunTest extends TestCase
{
    private DentalCase $case;

    protected function setUp(): void
    {
        parent::setUp();
        $this->case = DentalCase::factory()->create();
        $analysis = Analysis::query()->create(['case_id' => $this->case->id, 'position_strength' => 60, 'risk_level' => 'medium', 'result_json' => [], 'source_chunk_ids' => [], 'sources_sufficient' => true]);
        $recorder = app(AiRunRecorder::class);
        $call = fn (array $o = []): array => [...['model_requested' => 'model-a', 'model_served' => 'model-a', 'effort' => 'medium', 'inference_geo' => 'us', 'input_tokens' => 1000, 'output_tokens' => 400, 'cost_usd' => 0.02, 'latency_ms' => 1000], ...$o];
        // An analysis with a repair call: the first call is superseded, the second kept.
        $recorder->record(['run_type' => 'case_analysis', 'prompt_version' => 'analysis-v1', 'llm' => [$call(), $call(['latency_ms' => 3000, 'cost_usd' => 0.03])], 'citation_validity' => 0.9],
            [['chunk_id' => 11, 'ref' => 'S1'], ['chunk_id' => 12, 'ref' => 'S2']], 'ok', $analysis);
        $recorder->record(['run_type' => 'consult', 'llm' => [$call(['fallback_used' => true, 'model_served' => 'model-b', 'latency_ms' => 2000])]], [], 'answered');
        AiRun::query()->create(['run_type' => 'consult', 'status' => 'failed', 'error_code' => 'ai_unavailable']);
    }

    private function asAdmin(): void
    {
        $admin = User::factory()->create(['phone_e164' => null, 'email' => 'admin@dentlegal.test', 'password' => 'password-123', 'two_factor_confirmed_at' => now()]);
        $admin->assignRole('admin');
        $this->withHeaders(['Origin' => 'http://localhost', 'Referer' => 'http://localhost/'])
            ->actingAs($admin, 'web')->withSession([DashboardAuthController::PASSED => time()]);
    }

    public function test_admins_see_runs_with_case_codes_and_a_summary(): void
    {
        $this->asAdmin();
        $res = $this->getJson('/api/v1/admin/ai-runs')->assertOk();

        $rows = $res->json('data');
        $this->assertCount(4, $rows);
        $kept = collect($rows)->firstWhere('status', 'ok');
        $this->assertSame([$this->case->code, 'case_analysis', 2, 0.9], [$kept['case_code'], $kept['run_type'], $kept['sources'], $kept['citation_validity']]);
        $this->assertSame('superseded', collect($rows)->where('run_type', 'case_analysis')->firstWhere('status', '!=', 'ok')['status']);
        $this->assertNull(collect($rows)->firstWhere('run_type', 'consult')['case_code']);

        $res->assertJsonPath('meta.runs', 4)->assertJsonPath('meta.cost_usd', 0.07)->assertJsonPath('meta.fallbacks', 1)
            ->assertJsonPath('meta.errors', 1)->assertJsonPath('meta.latency_p50_ms', 2000)->assertJsonPath('meta.run_types', ['case_analysis', 'consult']);
        // Metadata only: no prompt or output text is part of a row.
        $this->assertSame([], array_intersect(array_keys($kept), ['prompt', 'output', 'question', 'answer', 'text']));
    }

    public function test_filters_narrow_rows_and_summary(): void
    {
        $this->asAdmin();
        $this->getJson('/api/v1/admin/ai-runs?run_type=consult')->assertJsonCount(2, 'data')->assertJsonPath('meta.runs', 2);
        $problems = $this->getJson('/api/v1/admin/ai-runs?problems=1')->json('data');
        $this->assertEqualsCanonicalizing(['answered', 'failed'], collect($problems)->pluck('status')->all());
        $this->getJson('/api/v1/admin/ai-runs?from=2099-01-01')->assertJsonCount(0, 'data')->assertJsonPath('meta.latency_p90_ms', null);
        $this->getJson('/api/v1/admin/ai-runs?problems=maybe')->assertUnprocessable();
    }

    public function test_lawyers_cannot_read_runs(): void
    {
        $lawyer = User::factory()->lawyer()->create(['two_factor_confirmed_at' => now()]);
        $this->withHeaders(['Origin' => 'http://localhost', 'Referer' => 'http://localhost/'])
            ->actingAs($lawyer, 'web')->withSession([DashboardAuthController::PASSED => time()]);
        $this->getJson('/api/v1/admin/ai-runs')->assertForbidden();
        $this->assertSame(4, DB::table('ai_runs')->count());
    }
}
