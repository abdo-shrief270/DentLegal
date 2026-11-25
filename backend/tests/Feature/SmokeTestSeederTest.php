<?php

namespace Tests\Feature;

use App\Modules\Identity\Http\Controllers\Dashboard\DashboardAuthController;
use App\Modules\Identity\Models\User;
use Database\Seeders\SmokeTestSeeder;
use Tests\TestCase;

/** The smoke-test seeder (web/dashboard/e2e) gives a lawyer one case to claim, and never runs in production. */
class SmokeTestSeederTest extends TestCase
{
    private const ENV = [
        'SMOKE_LAWYER_EMAIL' => 'lawyer.smoke@dentlegal.test', 'SMOKE_LAWYER_PASSWORD' => 'smoke-pass-123456', 'SMOKE_LAWYER_TOTP_SECRET' => 'JBSWY3DPEHPK3PXP',
        'SMOKE_ADMIN_EMAIL' => 'admin.smoke@dentlegal.test', 'SMOKE_ADMIN_PASSWORD' => 'smoke-pass-654321', 'SMOKE_ADMIN_TOTP_SECRET' => 'KRSXG5CTMVRXEZLU',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (self::ENV as $k => $v) {
            putenv("{$k}={$v}");
            $_ENV[$k] = $_SERVER[$k] = $v;
        }
    }

    protected function tearDown(): void
    {
        foreach (array_keys(self::ENV) as $k) {
            putenv($k);
            unset($_ENV[$k], $_SERVER[$k]);
        }
        parent::tearDown();
    }

    public function test_it_seeds_accounts_and_a_case_the_lawyer_can_claim_and_reruns_cleanly(): void
    {
        $this->seed(SmokeTestSeeder::class);
        $this->seed(SmokeTestSeeder::class); // a second run resets the accounts and adds another case

        $lawyer = User::query()->where('email', self::ENV['SMOKE_LAWYER_EMAIL'])->firstOrFail();
        $this->assertTrue($lawyer->hasRole('lawyer'));
        $this->assertSame('JBSWY3DPEHPK3PXP', $lawyer->two_factor_secret);
        $this->assertSame(2, User::query()->whereIn('email', [self::ENV['SMOKE_LAWYER_EMAIL'], self::ENV['SMOKE_ADMIN_EMAIL']])->count());

        $this->withHeaders(['Origin' => 'http://localhost', 'Referer' => 'http://localhost/'])
            ->actingAs($lawyer, 'web')->withSession([DashboardAuthController::PASSED => time()]);
        $queue = $this->getJson('/api/v1/lawyer/reviews?tab=unclaimed')->assertOk()->json('data');
        $this->assertCount(2, $queue);
        $this->postJson("/api/v1/lawyer/reviews/{$queue[0]['draft_id']}/claim")->assertOk();
        $this->getJson("/api/v1/lawyer/reviews/{$queue[0]['draft_id']}")->assertOk()->assertJsonPath('data.versions.0.author_type', 'ai');
    }

    public function test_it_refuses_production(): void
    {
        $this->app['env'] = 'production';
        $this->expectExceptionMessage('must not run in production');
        app(SmokeTestSeeder::class)->run();
    }
}
