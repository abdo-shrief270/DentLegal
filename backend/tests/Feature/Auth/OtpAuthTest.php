<?php

namespace Tests\Feature\Auth;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\Invitation;
use App\Modules\Identity\Models\OtpCode;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Sms\SmsGateway;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OtpAuthTest extends TestCase
{
    /** @var list<array{string, string}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(SmsGateway::class, new class($this->sent) implements SmsGateway
        {
            public function __construct(private array &$sent) {}

            public function send(string $phoneE164, string $message): void
            {
                $this->sent[] = [$phoneE164, $message];
            }
        });
    }

    private function invite(string $phone = '+966551111111'): Invitation
    {
        return Invitation::query()->create(['role' => Role::Dentist, 'phone_e164' => $phone, 'name' => 'د. تجريبي', 'expires_at' => now()->addDays(7)]);
    }

    private function lastCode(): string
    {
        preg_match('/(\d{6})/', end($this->sent)[1], $m);

        return $m[1];
    }

    private function verify(string $phone, string $code): TestResponse
    {
        return $this->postJson('/api/v1/auth/otp/verify', ['phone' => $phone, 'code' => $code, 'device' => ['platform' => 'ios', 'name' => 'iPhone', 'app_version' => '1.0.0 (1)']]);
    }

    public function test_invited_dentist_gets_a_code_and_first_login_creates_the_account(): void
    {
        $this->invite();

        $this->postJson('/api/v1/auth/otp/request', ['phone' => '0551111111'])
            ->assertStatus(202)
            ->assertJson(['data' => ['resend_after_s' => 60, 'expires_in_s' => 300]]);
        $this->assertSame('+966551111111', $this->sent[0][0]);

        $response = $this->verify('551111111', $this->lastCode())->assertOk()
            ->assertJsonPath('data.needs_profile', true)
            ->assertJsonPath('data.user.role', 'dentist');

        $user = User::query()->where('phone_e164', '+966551111111')->firstOrFail();
        $this->assertTrue($user->hasRole('dentist'));
        $this->assertNotNull(Invitation::query()->first()->accepted_at);
        $this->assertSame(1, $user->devices()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.otp_verified', 'actor_id' => $user->id]);

        $this->withToken($response->json('data.token'))->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.phone', '+966551111111');
    }

    public function test_numbers_without_invitation_are_refused_without_sending_sms(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '0500000000'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'invitation_required');
        $this->assertSame([], $this->sent);
    }

    public function test_invalid_phone_fails_validation_with_the_error_envelope(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '0126543210'])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['details' => ['fields' => ['phone']]], 'errors' => ['phone']]);
    }

    public function test_resend_is_throttled_for_60_seconds(): void
    {
        $this->invite();
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '0551111111'])->assertStatus(202);
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '0551111111'])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'rate_limited')
            ->assertHeader('Retry-After');
    }

    public function test_wrong_codes_are_counted_and_the_code_is_burnt_after_five(): void
    {
        $this->invite();
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '0551111111']);
        $good = $this->lastCode();
        $bad = $good === '000000' ? '111111' : '000000';

        for ($i = 4; $i >= 1; $i--) {
            $this->verify('0551111111', $bad)->assertUnprocessable()->assertJsonPath('error.code', 'otp_invalid')->assertJsonPath('error.details.attempts_left', $i);
        }
        $this->verify('0551111111', $bad)->assertJsonPath('error.details.attempts_left', 0);
        // Even the right code no longer works.
        $this->verify('0551111111', $good)->assertUnprocessable()->assertJsonPath('error.code', 'otp_expired');
        $this->assertDatabaseMissing('users', ['phone_e164' => '+966551111111']);
    }

    public function test_expired_codes_are_refused(): void
    {
        $this->invite();
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '0551111111']);
        $code = $this->lastCode();
        $this->travel(301)->seconds();

        $this->verify('0551111111', $code)->assertJsonPath('error.code', 'otp_expired');
    }

    public function test_codes_are_stored_hashed(): void
    {
        $this->invite();
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '0551111111']);
        $code = $this->lastCode();

        $stored = OtpCode::query()->firstOrFail();
        $this->assertNotSame($code, $stored->code_hash);
        $this->assertSame(OtpCode::hashFor('+966551111111', $code), $stored->code_hash);
    }

    public function test_disabled_accounts_cannot_request_codes(): void
    {
        User::factory()->dentist()->disabled()->create(['phone_e164' => '+966552222222']);

        $this->postJson('/api/v1/auth/otp/request', ['phone' => '0552222222'])->assertForbidden()->assertJsonPath('error.code', 'account_disabled');
    }

    public function test_lawyers_cannot_sign_in_to_the_app(): void
    {
        User::factory()->lawyer()->create(['phone_e164' => '+966553333333']);

        $this->postJson('/api/v1/auth/otp/request', ['phone' => '0553333333'])->assertForbidden()->assertJsonPath('error.code', 'invitation_required');
    }

    public function test_old_app_builds_must_update(): void
    {
        $this->withHeaders(['X-App-Version' => '0.9.0'])
            ->postJson('/api/v1/auth/otp/request', ['phone' => '0551111111'])
            ->assertStatus(426)
            ->assertJsonPath('error.code', 'app_update_required');
    }

    public function test_review_number_uses_the_fixed_code_without_sms(): void
    {
        config(['dentlegal.otp.review_phone' => '+966559999999', 'dentlegal.otp.review_code' => '424242']);
        $this->invite('+966559999999');

        $this->postJson('/api/v1/auth/otp/request', ['phone' => '0559999999'])->assertStatus(202);
        $this->assertSame([], $this->sent);
        $this->verify('0559999999', '424242')->assertOk();
    }
}
