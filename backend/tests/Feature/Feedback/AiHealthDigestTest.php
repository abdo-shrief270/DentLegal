<?php

namespace Tests\Feature\Feedback;

use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Models\AiRun;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\SentMessage;
use Tests\TestCase;

/** `ai:health-digest` (PROJECT1_PLAN.md §8.6): counts and flags for the last 24 hours. Synthetic rows only. */
class AiHealthDigestTest extends TestCase
{
    private function answers(int $answered, int $refused): void
    {
        $thread = (string) Str::uuid7();
        DB::table('consultations')->insert(['id' => $thread, 'user_id' => User::factory()->dentist()->create()->id, 'title' => 'اختبار',
            'last_message_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        foreach ([...array_fill(0, $answered, 'answered'), ...array_fill(0, $refused, 'refused')] as $status) {
            DB::table('consultation_messages')->insert(['id' => (string) Str::uuid7(), 'consultation_id' => $thread, 'role' => 'assistant',
                'status' => $status, 'answered_at' => now()->subHour(), 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    /** @return list<SentMessage> */
    private function mails(): array
    {
        return array_values(app('mailer')->getSymfonyTransport()->messages()->all());
    }

    public function test_a_bad_day_is_flagged_by_email_and_webhook(): void
    {
        Config::set('dentlegal.ai_health.recipients', ['ops@dentlegal.test', 'client@dentlegal.test']);
        Config::set('dentlegal.ai_health.webhook_url', 'https://hooks.example.test/alert');
        Config::set('dentlegal.ai_health.daily_cost_max_usd', 1.0);
        Http::fake();
        $this->answers(8, 4); // 33% refusals
        foreach ([0.80, 0.90, 1.0] as $v) {
            AiRun::query()->create(['run_type' => 'case_analysis', 'status' => 'ok', 'cost_usd' => 0.6, 'citation_validity' => $v]);
        }
        AiRun::query()->create(['run_type' => 'consult', 'status' => 'failed', 'error_code' => 'ai_unavailable']);
        AiRun::query()->create(['run_type' => 'consult', 'status' => 'answered', 'cost_usd' => 0.2, 'created_at' => now()->subDays(2)]); // outside the window

        $this->artisan('ai:health-digest')->assertSuccessful();

        $mail = $this->mails();
        $this->assertCount(1, $mail);
        $body = (string) $mail[0]->getOriginalMessage()->getTextBody();
        $this->assertStringContainsString('تنبيه', (string) $mail[0]->getOriginalMessage()->getSubject());
        $this->assertStringContainsString('نسبة الاعتذار 33.3%', $body);
        $this->assertStringContainsString('صحة الاستشهادات 90%', $body);
        $this->assertStringContainsString('التكلفة $1.80', $body);
        $this->assertStringContainsString('1 طلبات فاشلة', $body);
        Http::assertSent(fn ($r) => $r->url() === 'https://hooks.example.test/alert' && str_contains($r['text'], 'نسبة الاعتذار'));
    }

    public function test_a_quiet_day_is_reported_without_flags_or_webhook(): void
    {
        Config::set('dentlegal.ai_health.recipients', ['ops@dentlegal.test']);
        Config::set('dentlegal.ai_health.webhook_url', 'https://hooks.example.test/alert');
        Http::fake();
        $this->answers(3, 2); // a high rate on too few answers is not flagged

        $this->artisan('ai:health-digest')->assertSuccessful();

        $body = (string) $this->mails()[0]->getOriginalMessage()->getTextBody();
        $this->assertStringContainsString('لا توجد ملاحظات', $body);
        $this->assertStringContainsString('اعتذارات: 2 (40%)', $body);
        Http::assertNothingSent();
    }

    public function test_it_is_scheduled_daily(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('ai:health-digest')->assertSuccessful();
    }
}
