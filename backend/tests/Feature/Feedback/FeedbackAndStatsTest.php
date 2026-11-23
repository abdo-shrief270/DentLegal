<?php

namespace Tests\Feature\Feedback;

use App\Modules\Cases\Models\DentalCase;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Consultations\Models\ConsultationMessage;
use App\Modules\Feedback\Models\Feedback;
use App\Modules\Identity\Http\Controllers\Dashboard\DashboardAuthController;
use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Models\Analysis;
use App\Modules\Pipeline\Models\Draft;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Ratings (S6.1), feedback (S6.2), admin views with CSV (S7.3) and usage stats (S6.3). Synthetic data only. */
class FeedbackAndStatsTest extends TestCase
{
    private User $dentist;

    private ConsultationMessage $answer;

    private Analysis $analysis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dentist = $this->actingAsDentist();
        $consultation = Consultation::query()->create(['user_id' => $this->dentist->id, 'title' => 'سؤال', 'last_message_at' => now()]);
        $question = ConsultationMessage::query()->create(['consultation_id' => $consultation->id, 'role' => 'user', 'text_masked' => 'هل التأمين ضد الأخطاء إلزامي؟']);
        $this->answer = ConsultationMessage::query()->create(['consultation_id' => $consultation->id, 'role' => 'assistant', 'reply_to_id' => $question->id, 'status' => 'answered', 'answer_json' => ['paragraphs' => []], 'answered_at' => now()]);
        $case = DentalCase::factory()->for($this->dentist, 'dentist')->create();
        $this->analysis = Analysis::query()->create(['case_id' => $case->id, 'position_strength' => 70, 'risk_level' => 'low', 'result_json' => ['summary' => 'ملخص التحليل.'],
            'source_chunk_ids' => [], 'sources_sufficient' => true, 'needs_attention' => false, 'is_current' => true]);
    }

    private function asAdmin(): void
    {
        $admin = User::factory()->create(['phone_e164' => null, 'email' => 'admin@dentlegal.test', 'password' => 'password-123',
            'two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'two_factor_confirmed_at' => now()]);
        $admin->assignRole('admin');
        $this->app['auth']->forgetGuards();
        $this->withHeaders(['Origin' => 'http://localhost', 'Referer' => 'http://localhost/'])->actingAs($admin, 'web')->withSession([DashboardAuthController::PASSED => time()]);
    }

    public function test_ratings_upsert_and_are_limited_to_own_items(): void
    {
        $this->postJson('/api/v1/ratings', ['target_type' => 'consultation_message', 'target_id' => $this->answer->id, 'useful' => false, 'comment' => 'جوالي 0551234567'])->assertCreated();
        $this->postJson('/api/v1/ratings', ['target_type' => 'consultation_message', 'target_id' => $this->answer->id, 'useful' => true])->assertOk();
        $this->assertDatabaseCount('ratings', 1);
        $this->assertDatabaseHas('ratings', ['useful' => true]);

        $other = DentalCase::factory()->create();
        $foreign = Analysis::query()->create(['case_id' => $other->id, 'position_strength' => 50, 'risk_level' => 'high', 'result_json' => [], 'source_chunk_ids' => [], 'sources_sufficient' => false, 'needs_attention' => false, 'is_current' => true]);
        $this->postJson('/api/v1/ratings', ['target_type' => 'analysis', 'target_id' => $foreign->id, 'useful' => true])->assertNotFound();

        // Lawyer work in progress can't be rated; the AI version can.
        $draft = Draft::query()->create(['case_id' => $this->analysis->case_id, 'status' => 'in_review']);
        $ai = $draft->versions()->create(['version_no' => 1, 'author_type' => 'ai', 'content_json' => ['title' => 'مسودة أولى']]);
        $wip = $draft->versions()->create(['version_no' => 2, 'author_type' => 'lawyer', 'content_json' => ['title' => 'تعديل']]);
        $this->postJson('/api/v1/ratings', ['target_type' => 'draft_version', 'target_id' => $wip->id, 'useful' => true])->assertNotFound();
        $this->postJson('/api/v1/ratings', ['target_type' => 'draft_version', 'target_id' => $ai->id, 'useful' => true])->assertCreated();
    }

    public function test_feedback_is_masked_and_carries_the_app_version(): void
    {
        $this->postJson('/api/v1/feedback', ['category' => 'bug', 'message' => 'التطبيق يتوقف، تواصلوا معي على 0551234567', 'screen' => 'case_wizard', 'platform' => 'ios'])->assertCreated();

        $feedback = Feedback::query()->firstOrFail();
        $this->assertStringNotContainsString('0551234567', $feedback->message);
        $this->assertSame('1.0.0 (1)', $feedback->app_version);
        $this->assertStringNotContainsString('تواصلوا', (string) DB::table('feedback')->value('message'), 'stored encrypted');
    }

    public function test_admin_lists_filters_csv_and_status(): void
    {
        $this->postJson('/api/v1/ratings', ['target_type' => 'analysis', 'target_id' => $this->analysis->id, 'useful' => false, 'comment' => '=HYPERLINK("x")']);
        $this->postJson('/api/v1/ratings', ['target_type' => 'consultation_message', 'target_id' => $this->answer->id, 'useful' => true]);
        $this->postJson('/api/v1/feedback', ['category' => 'suggestion', 'message' => 'أقترح إضافة وضع ليلي.']);
        $this->asAdmin();

        $this->getJson('/api/v1/admin/ratings')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/admin/ratings?useful=0')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.target_label', 'تحليل '.$this->analysis->case->code)->assertJsonPath('data.0.target_excerpt', 'ملخص التحليل.');
        $csv = $this->get('/api/v1/admin/ratings?format=csv')->assertOk()->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBFcreated_at,", $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv, 'formula cells are neutralised');

        $id = $this->getJson('/api/v1/admin/feedback?status=new')->assertOk()->assertJsonCount(1, 'data')->json('data.0.id');
        $this->patchJson("/api/v1/admin/feedback/{$id}", ['status' => 'done'])->assertOk();
        $this->getJson('/api/v1/admin/feedback?status=new')->assertJsonCount(0, 'data');
    }

    public function test_stats_overview_and_roll_up(): void
    {
        $this->postJson('/api/v1/ratings', ['target_type' => 'consultation_message', 'target_id' => $this->answer->id, 'useful' => true]);
        $this->postJson('/api/v1/ratings', ['target_type' => 'analysis', 'target_id' => $this->analysis->id, 'useful' => false]);
        $this->dentist->createToken('app', ['dentist'])->accessToken->forceFill(['last_used_at' => now()])->save();
        DB::table('ai_runs')->insert(['run_type' => 'consult', 'status' => 'succeeded', 'cost_usd' => 0.05, 'citation_validity' => 0.9, 'created_at' => now()->subDays(2)]);
        DB::table('cases')->where('id', $this->analysis->case_id)->update(['created_at' => now()->subDays(2)]);

        $this->artisan('stats:roll-up', ['--days' => 3])->assertSuccessful();
        $this->assertSame(1.0, (float) DB::table('daily_usage_stats')->where('day', now()->subDays(2)->toDateString())->where('metric', 'cases')->value('value'));

        $this->asAdmin();
        $data = $this->getJson('/api/v1/admin/stats/overview?range=7d')->assertOk()->json('data');
        $this->assertSame(1, $data['active_dentists']);
        $this->assertSame(1, $data['questions_answered']);
        $this->assertSame(1, $data['cases_created']);
        $this->assertEquals(0.5, $data['useful_rate']['overall']);
        $this->assertEquals(1.0, $data['useful_rate']['consult']);
        $this->assertEquals(0.05, $data['ai_cost_usd']);
        $this->assertEquals(0.9, $data['citation_validity']);
        $this->assertCount(7, $data['daily']);
        $this->assertSame(1, $data['daily'][4]['cases']); // two days ago, from the roll-up
        $this->assertSame(1, $data['daily'][6]['questions']); // today, live
    }

    public function test_dentists_cannot_read_admin_views(): void
    {
        Sanctum::actingAs($this->dentist, ['dentist']);

        $this->getJson('/api/v1/admin/ratings')->assertForbidden();
    }
}
