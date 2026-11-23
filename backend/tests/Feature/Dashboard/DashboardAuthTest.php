<?php

namespace Tests\Feature\Dashboard;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\Invitation;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\TwoFactor\Totp;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Dashboard sign-in, two-factor and invite links (S5.7). Synthetic accounts only. */
class DashboardAuthTest extends TestCase
{
    private const PROBE = '/api/v1/lawyer/_probe';

    protected function setUp(): void
    {
        parent::setUp();
        // Requests come from the dashboard origin, so Sanctum treats them as a cookie session.
        $this->withHeaders(['Origin' => 'http://localhost', 'Referer' => 'http://localhost/']);
        Route::middleware(['api', 'auth:sanctum', 'two_factor'])->get(self::PROBE, fn () => response()->json(['ok' => true]));
    }

    private function enrolledLawyer(): User
    {
        $lawyer = User::factory()->lawyer()->create();
        $lawyer->forceFill(['two_factor_secret' => Totp::newSecret(), 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => [hash('sha256', 'AAAAA-BBBBB')]])->save();

        return $lawyer;
    }

    private function codeFor(User $user, int $offset = 0): string
    {
        return Totp::code((string) $user->refresh()->two_factor_secret, intdiv(time(), Totp::PERIOD) + $offset);
    }

    public function test_invite_link_sets_the_password_and_enrolment_is_required_before_any_lawyer_route(): void
    {
        $token = Str::random(48);
        Invitation::query()->create(['role' => Role::Lawyer, 'name' => 'أ. ليلى المراجِعة', 'email' => 'Laila@dentlegal.test', 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(7)]);

        $this->getJson("/invitations/{$token}")->assertOk()->assertJsonPath('data.email', 'Laila@dentlegal.test');
        $this->postJson("/invitations/{$token}/accept", ['password' => 'short'])->assertUnprocessable();
        $setup = $this->postJson("/invitations/{$token}/accept", ['password' => 'Correct-horse-42'])->assertCreated()
            ->assertJsonCount(8, 'data.recovery_codes')->json('data');
        $this->assertStringStartsWith('otpauth://totp/DentLegal:laila%40dentlegal.test?secret=', $setup['otpauth_uri']);

        $this->getJson('/user')->assertOk()->assertJsonPath('data.role', 'lawyer')->assertJsonPath('data.two_factor.enabled', false);
        $this->getJson(self::PROBE)->assertForbidden()->assertJsonPath('error.code', 'two_factor_required');

        $this->postJson('/user/confirmed-two-factor-authentication', ['code' => '000000'])->assertUnprocessable()->assertJsonPath('error.code', 'invalid_code');
        $code = Totp::code($setup['secret'], intdiv(time(), Totp::PERIOD));
        $this->postJson('/user/confirmed-two-factor-authentication', ['code' => $code])->assertOk()->assertJsonPath('data.two_factor.enabled', true);
        $this->getJson(self::PROBE)->assertOk();

        $this->postJson("/invitations/{$token}/accept", ['password' => 'Correct-horse-42'])->assertNotFound()->assertJsonPath('error.code', 'invitation_invalid');
        $this->assertDatabaseHas('audit_logs', ['action' => 'two_factor.enabled']);
    }

    public function test_login_needs_the_code_and_a_code_works_once(): void
    {
        $lawyer = $this->enrolledLawyer();

        $this->postJson('/login', ['email' => $lawyer->email, 'password' => 'password-123'])->assertOk()->assertJsonPath('data.two_factor_required', true);
        $this->getJson(self::PROBE)->assertUnauthorized();
        $this->postJson('/two-factor-challenge', ['code' => '123456'])->assertUnprocessable();

        $code = $this->codeFor($lawyer);
        $this->postJson('/two-factor-challenge', ['code' => $code])->assertOk()->assertJsonPath('data.two_factor.passed', true);
        $this->getJson(self::PROBE)->assertOk();

        $this->postJson('/logout')->assertNoContent();
        $this->app['auth']->forgetGuards(); // the test app keeps resolved guards between requests; a real request starts clean
        $this->getJson(self::PROBE)->assertUnauthorized();

        $this->postJson('/login', ['email' => $lawyer->email, 'password' => 'password-123'])->assertOk();
        $this->postJson('/two-factor-challenge', ['code' => $code])->assertUnprocessable()->assertJsonPath('error.code', 'invalid_code');
    }

    public function test_a_recovery_code_works_once(): void
    {
        $lawyer = $this->enrolledLawyer();

        $this->postJson('/login', ['email' => $lawyer->email, 'password' => 'password-123']);
        $this->postJson('/two-factor-challenge', ['recovery_code' => 'aaaaa-bbbbb'])->assertOk();
        $this->postJson('/logout');
        $this->app['auth']->forgetGuards();

        $this->postJson('/login', ['email' => $lawyer->email, 'password' => 'password-123']);
        $this->postJson('/two-factor-challenge', ['recovery_code' => 'AAAAA-BBBBB'])->assertUnprocessable();
        $this->assertDatabaseHas('audit_logs', ['action' => 'two_factor.recovery_code_used']);
    }

    public function test_wrong_password_dentists_and_disabled_accounts_get_the_same_error(): void
    {
        $lawyer = $this->enrolledLawyer();
        $dentist = User::factory()->dentist()->create(['email' => 'dentist@dentlegal.test', 'password' => 'password-123']);
        $disabled = User::factory()->lawyer()->disabled()->create();

        foreach ([[$lawyer->email, 'wrong-password'], [$dentist->email, 'password-123'], [$disabled->email, 'password-123'], ['nobody@dentlegal.test', 'password-123']] as [$email, $password]) {
            $this->postJson('/login', ['email' => $email, 'password' => $password])->assertUnprocessable()->assertJsonPath('error.code', 'invalid_credentials');
        }
    }

    public function test_login_is_throttled_per_email_and_ip(): void
    {
        $lawyer = $this->enrolledLawyer();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/login', ['email' => $lawyer->email, 'password' => 'wrong-password']);
        }

        $this->postJson('/login', ['email' => $lawyer->email, 'password' => 'password-123'])->assertStatus(429);
    }

    public function test_codes_are_throttled(): void
    {
        $lawyer = $this->enrolledLawyer();
        $this->postJson('/login', ['email' => $lawyer->email, 'password' => 'password-123']);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/two-factor-challenge', ['code' => '000000']);
        }

        $this->postJson('/two-factor-challenge', ['code' => $this->codeFor($lawyer)])->assertStatus(429);
    }

    public function test_the_pending_challenge_expires(): void
    {
        $lawyer = $this->enrolledLawyer();
        $this->postJson('/login', ['email' => $lawyer->email, 'password' => 'password-123']);

        $this->travel(6)->minutes();
        $this->postJson('/two-factor-challenge', ['code' => $this->codeFor($lawyer)])->assertUnauthorized()->assertJsonPath('error.code', 'login_expired');
    }

    public function test_api_tokens_never_open_dashboard_routes(): void
    {
        Sanctum::actingAs($this->enrolledLawyer(), ['*']);

        $this->getJson(self::PROBE)->assertForbidden()->assertJsonPath('error.code', 'session_required');
    }
}
