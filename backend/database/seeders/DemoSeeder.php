<?php

namespace Database\Seeders;

use App\Modules\Cases\Enums\CaseStatus;
use App\Modules\Cases\Models\DentalCase;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\Invitation;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;

/**
 * Synthetic demo data for dev and staging (never production): the same demo dentist as the app's fake mode
 * (+966551234417), an open invitation for a new dentist, and three cases.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('DemoSeeder must not run in production.');
        }

        $this->call(RoleSeeder::class);

        $dentist = User::query()->where('phone_e164', '+966551234417')->first()
            ?? User::factory()->dentist()->create(['name' => 'د. أحمد بن سعد الغامدي (اسم تجريبي)', 'phone_e164' => '+966551234417']);

        Invitation::query()->firstOrCreate(
            ['phone_e164' => '+966551111111', 'role' => Role::Dentist->value],
            ['name' => 'د. طبيب مدعو (تجريبي)', 'expires_at' => now()->addDays(30)],
        );

        if (DentalCase::query()->where('dentist_id', $dentist->id)->doesntExist()) {
            DentalCase::factory()->for($dentist, 'dentist')->status(CaseStatus::AiReady)->create();
            DentalCase::factory()->for($dentist, 'dentist')->status(CaseStatus::LawyerReview)->create(['title' => 'ادعاء تلف عصب بعد علاج جذور']);
            DentalCase::factory()->for($dentist, 'dentist')->status(CaseStatus::Approved)->create(['title' => 'مضاعفات بعد خلع ضرس العقل السفلي']);
        }
    }
}
