<?php

namespace Tests\Feature\Cases;

use App\Modules\Cases\Actions\NextCaseCode;
use App\Modules\Cases\Actions\TransitionCase;
use App\Modules\Cases\Enums\CaseStatus;
use App\Modules\Cases\Models\DentalCase;
use App\Modules\Identity\Models\User;
use App\Support\Api\ApiException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CaseApiTest extends TestCase
{
    /** @return array<string, string> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'case_type' => 'complaint',
            'authority' => 'المديرية العامة للشؤون الصحية بمحافظة جدة',
            'complaint_no' => '02871/1448',
            'received_on' => now()->subDays(3)->toDateString(),
            'title' => 'ألم مستمر بعد علاج عصب',
            'procedure' => 'علاج عصب الضرس 36',
            'procedure_date' => now()->subMonth()->toDateString(),
            'patient_code' => 'P-2291',
            'claim_type' => 'compensation',
            'facts' => 'تفيد الشكوى بأن المريض راجع بألم مستمر بعد العلاج وأن الطبيب لم يوضح المضاعفات المحتملة.',
        ], $overrides);
    }

    public function test_dentist_creates_a_case_with_a_sequential_code(): void
    {
        $this->actingAsDentist();
        $year = now()->year;

        $first = $this->postJson('/api/v1/cases', $this->payload())->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.actions.can_upload_documents', true)
            ->json('data.code');
        $second = $this->postJson('/api/v1/cases', $this->payload())->json('data.code');

        $this->assertSame("DL-{$year}-0001", $first);
        $this->assertSame("DL-{$year}-0002", $second);
    }

    public function test_identifiers_in_typed_facts_are_never_stored(): void
    {
        $this->actingAsDentist();

        $id = $this->postJson('/api/v1/cases', $this->payload([
            'facts' => 'المريض صاحب الهوية 1023456789 اتصل من الجوال 0551234567 وادعى حدوث مضاعفات بعد العلاج.',
        ]))->assertCreated()
            ->assertJsonPath('data.masked_entities.ID', 1)
            ->assertJsonPath('data.masked_entities.PHONE', 1)
            ->json('data.id');

        $stored = DentalCase::query()->findOrFail($id);
        $this->assertStringNotContainsString('1023456789', $stored->facts_masked);
        $this->assertStringContainsString('[ID_1]', $stored->facts_masked);
        // The column is encrypted at rest: the raw value in the database is not readable text.
        $raw = DB::table('cases')->where('id', $id)->value('facts_masked');
        $this->assertStringNotContainsString('المريض', $raw);
    }

    public function test_patient_names_are_rejected_as_patient_code(): void
    {
        $this->actingAsDentist();

        $this->postJson('/api/v1/cases', $this->payload(['patient_code' => 'محمد أحمد']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('patient_code');
    }

    public function test_dentists_without_a_completed_profile_cannot_create_cases(): void
    {
        $this->actingAsDentist(User::factory()->dentist(withProfile: false)->create());

        $this->postJson('/api/v1/cases', $this->payload())->assertForbidden();
    }

    public function test_other_dentists_cases_look_missing(): void
    {
        $other = DentalCase::factory()->create();
        $this->actingAsDentist();

        $this->getJson("/api/v1/cases/{$other->id}")->assertNotFound()->assertJsonPath('error.code', 'not_found');
    }

    public function test_list_is_scoped_filtered_and_cursor_paginated(): void
    {
        $me = $this->actingAsDentist();
        DentalCase::factory()->count(3)->for($me, 'dentist')->create();
        DentalCase::factory()->for($me, 'dentist')->status(CaseStatus::Approved)->create();
        DentalCase::factory()->create(); // someone else's

        $this->getJson('/api/v1/cases?per_page=2')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.per_page', 2);
        $this->getJson('/api/v1/cases?status=approved')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/cases?per_page=50')->assertJsonCount(4, 'data');
    }

    public function test_invalid_transitions_are_409_and_valid_ones_are_audited(): void
    {
        $case = DentalCase::factory()->create();
        $transition = app(TransitionCase::class);

        try {
            $transition($case, CaseStatus::Approved);
            $this->fail('Expected invalid_state');
        } catch (ApiException $e) {
            $this->assertSame(409, $e->status);
            $this->assertSame('invalid_state', $e->errorCode);
        }

        $updated = $transition($case, CaseStatus::Analyzing);
        $this->assertSame(CaseStatus::Analyzing, $updated->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'case.status_changed', 'subject_id' => $case->id]);

        $transition($updated, CaseStatus::Failed, failureCode: 'ocr_failed');
        $this->assertSame('ocr_failed', $case->refresh()->failure_code);
        $transition($case, CaseStatus::Analyzing);
        $this->assertNull($case->refresh()->failure_code);
    }

    public function test_case_codes_restart_each_year(): void
    {
        $next = app(NextCaseCode::class);

        $this->assertSame('DL-2030-0001', $next(2030));
        $this->assertSame('DL-2030-0002', $next(2030));
        $this->assertSame('DL-2031-0001', $next(2031));
    }
}
