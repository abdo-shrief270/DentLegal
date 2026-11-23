<?php

namespace Database\Factories;

use App\Modules\Cases\Actions\NextCaseCode;
use App\Modules\Cases\Enums\CaseStatus;
use App\Modules\Cases\Enums\CaseType;
use App\Modules\Cases\Enums\ClaimType;
use App\Modules\Cases\Models\DentalCase;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Synthetic cases only.
 *
 * @extends Factory<DentalCase>
 */
class DentalCaseFactory extends Factory
{
    protected $model = DentalCase::class;

    public function definition(): array
    {
        return [
            'code' => app(NextCaseCode::class)(),
            'dentist_id' => User::factory()->dentist(),
            'case_type' => CaseType::Complaint,
            'authority' => 'المديرية العامة للشؤون الصحية بمحافظة جدة — الشؤون القانونية',
            'complaint_no' => fake()->numerify('#####/1448'),
            'received_on' => now()->subDays(10)->toDateString(),
            'title' => 'تنميل في الشفة السفلية بعد زراعة سن',
            'procedure' => 'زراعة سن في موضع الضرس (36)',
            'procedure_date' => now()->subMonths(2)->toDateString(),
            'patient_code' => 'P-'.fake()->numerify('####'),
            'claim_type' => ClaimType::Compensation,
            'facts_masked' => 'يدّعي المريض استمرار تنميل في الشفة السفلية بعد إجراء الزراعة ويطالب بالتعويض.',
            'facts_entity_counts' => [],
            'status' => CaseStatus::Draft,
        ];
    }

    public function status(CaseStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
