<?php

namespace Tests\Feature\Auth;

use App\Modules\Identity\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class MeTest extends TestCase
{
    public function test_profile_completion_and_consents(): void
    {
        $user = $this->actingAsDentist(User::factory()->dentist(withProfile: false)->create());

        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.needs_profile', true);

        $this->putJson('/api/v1/me/profile', [
            'name' => 'د. اسم تجريبي', 'specialty' => 'طب أسنان عام', 'city' => 'جدة', 'facility_name' => 'عيادة تجريبية', 'scfhs_license_no' => '21-RD-0000001',
        ])->assertOk()->assertJsonPath('data.needs_profile', false)->assertJsonPath('data.profile.city', 'جدة');

        $versions = config('dentlegal.consent_versions');
        $this->postJson('/api/v1/me/consents', ['consents' => [
            ['type' => 'terms', 'version' => $versions['terms']],
            ['type' => 'ai_processing', 'version' => $versions['ai_processing']],
        ]])->assertOk()
            ->assertJsonPath('data.consents.terms.granted', true)
            ->assertJsonPath('data.consents.ai_processing.granted', true)
            ->assertJsonPath('data.consents.lawyer_access.granted', false);

        // Granting again is a no-op (consents are append-only).
        $this->postJson('/api/v1/me/consents', ['consents' => [['type' => 'terms', 'version' => $versions['terms']]]])->assertOk();
        $this->assertSame(2, $user->consents()->count());
    }

    public function test_outdated_consent_versions_are_refused(): void
    {
        $this->actingAsDentist();

        $this->postJson('/api/v1/me/consents', ['consents' => [['type' => 'terms', 'version' => '2020-01']]])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'consent_version_outdated');
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->dentist()->create();
        $a = $user->createToken('a', ['dentist']);
        $b = $user->createToken('b', ['dentist']);

        $this->withToken($a->plainTextToken)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->assertNull(PersonalAccessToken::find($a->accessToken->id));
        $this->assertNotNull(PersonalAccessToken::find($b->accessToken->id));
    }

    public function test_account_deletion_signs_out_everywhere_and_frees_the_phone(): void
    {
        $user = User::factory()->dentist()->create(['phone_e164' => '+966554444444']);
        $token = $user->createToken('a', ['dentist'])->plainTextToken;

        $this->withToken($token)->deleteJson('/api/v1/me')->assertNoContent();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('users', ['phone_e164' => '+966554444444']);
        $this->assertSame(0, PersonalAccessToken::query()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.deletion_requested', 'subject_id' => $user->id]);
    }

    public function test_tokens_without_the_dentist_ability_are_refused(): void
    {
        $user = User::factory()->dentist()->create();
        $token = $user->createToken('other', ['something-else'])->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/me')->assertForbidden();
    }

    public function test_unauthenticated_requests_get_401_envelope(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
    }
}
