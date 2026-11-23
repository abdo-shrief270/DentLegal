<?php

namespace Tests\Feature\Compliance;

use App\Modules\Cases\Models\DentalCase;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Documents\Actions\UploadDocument;
use App\Modules\Documents\Enums\DocumentKind;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\Invitation;
use App\Modules\Identity\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Retention and account purge (PROJECT1_PLAN.md §9.7, S9.2). Synthetic data only. */
class RetentionTest extends TestCase
{
    public function test_a_deleted_account_is_purged_after_the_grace_period(): void
    {
        Storage::fake('documents');
        $dentist = $this->actingAsDentist();
        Invitation::query()->create(['role' => Role::Dentist, 'name' => $dentist->name, 'phone_e164' => $dentist->phone_e164, 'expires_at' => now()->addDay(), 'accepted_at' => now()]);
        $case = DentalCase::factory()->for($dentist, 'dentist')->create();
        $doc = app(UploadDocument::class)($dentist, $case, UploadedFile::fake()->createWithContent('letter.pdf', "%PDF-1.7\nsynthetic"), DocumentKind::ComplaintLetter);
        Consultation::query()->create(['user_id' => $dentist->id, 'title' => 'سؤال', 'last_message_at' => now()]);
        $this->postJson('/api/v1/feedback', ['category' => 'other', 'message' => 'ملاحظة تجريبية.'])->assertCreated();
        $dentist->devices()->create(['platform' => 'ios', 'fcm_token' => 'tok']);

        $phone = $dentist->phone_e164;
        $this->deleteJson('/api/v1/me')->assertSuccessful();
        $this->assertDatabaseMissing('invitations', ['phone_e164' => $phone]);
        $this->assertDatabaseMissing('invitations', ['name' => $dentist->name]);

        // Within the grace period nothing is removed yet.
        $this->travel(3)->days();
        $this->artisan('retention:purge')->assertSuccessful();
        $this->assertDatabaseHas('cases', ['id' => $case->id]);

        $this->travel(5)->days();
        $this->artisan('retention:purge')->expectsOutputToContain('accounts     1')->assertSuccessful();

        $this->assertDatabaseMissing('cases', ['id' => $case->id]);
        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
        Storage::disk('documents')->assertMissing($doc->storage_key);
        $this->assertDatabaseCount('consultations', 0);
        $this->assertDatabaseCount('feedback', 0);
        $this->assertDatabaseCount('devices', 0);
        $this->assertDatabaseCount('dentist_profiles', 0);
        $row = DB::table('users')->where('id', $dentist->id)->first();
        $this->assertNull($row->name);
        $this->assertNotNull($row->purged_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.purged', 'subject_id' => $dentist->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.deletion_requested', 'actor_id' => $dentist->id]); // the history stays

        // A second run has nothing left to do for this account.
        $this->artisan('retention:purge')->expectsOutputToContain('accounts     0')->assertSuccessful();
    }

    public function test_short_lived_data_and_old_audit_rows_expire(): void
    {
        $user = User::factory()->dentist()->create();
        DB::table('otp_codes')->insert([
            ['id' => '01900000-0000-7000-8000-000000000001', 'phone_e164' => '+966500000001', 'code_hash' => str_repeat('a', 64), 'attempts' => 0, 'expires_at' => now()->subDays(2), 'created_at' => now()->subDays(2)],
            ['id' => '01900000-0000-7000-8000-000000000002', 'phone_e164' => '+966500000001', 'code_hash' => str_repeat('b', 64), 'attempts' => 0, 'expires_at' => now()->addMinutes(5), 'created_at' => now()],
        ]);
        $idle = $user->createToken('old', ['dentist'])->accessToken;
        $idle->forceFill(['last_used_at' => now()->subDays(31)])->save();
        $fresh = $user->createToken('fresh', ['dentist'])->accessToken;
        $fresh->forceFill(['last_used_at' => now()->subDay()])->save();
        DB::table('ai_runs')->insert([
            ['run_type' => 'consult', 'status' => 'succeeded', 'cost_usd' => 0.05, 'created_at' => now()->subMonths(13)],
            ['run_type' => 'consult', 'status' => 'succeeded', 'cost_usd' => 0.05, 'created_at' => now()->subMonth()],
        ]);
        DB::table('audit_logs')->insert(['occurred_at' => now()->subYears(3), 'action' => 'old.event', 'metadata' => '{}']);

        $this->artisan('retention:purge', ['--dry-run' => true])->expectsOutputToContain('otp_codes    1 (dry run)')->assertSuccessful();
        $this->assertDatabaseCount('otp_codes', 2);

        $this->artisan('retention:purge')->assertSuccessful();

        $this->assertDatabaseCount('otp_codes', 1);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $idle->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $fresh->id]);
        $this->assertDatabaseCount('ai_runs', 1);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'old.event']);
    }

    public function test_recent_audit_rows_stay_append_only(): void
    {
        DB::table('audit_logs')->insert(['occurred_at' => now()->subYear(), 'action' => 'recent.event', 'metadata' => '{}']);

        $this->expectException(QueryException::class);
        DB::table('audit_logs')->where('action', 'recent.event')->delete();
    }
}
