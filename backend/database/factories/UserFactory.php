<?php

namespace Database\Factories;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\DentistProfile;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Synthetic people only: no real names, phones or licence numbers.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => 'د. '.fake()->firstNameMale().' '.fake()->lastName(),
            'phone_e164' => '+9665'.fake()->unique()->numerify('########'),
            'status' => UserStatus::Active,
        ];
    }

    /** A dentist who has completed the profile. */
    public function dentist(bool $withProfile = true): static
    {
        return $this->afterCreating(function (User $user) use ($withProfile): void {
            $user->assignRole(Role::Dentist->value);
            if ($withProfile) {
                DentistProfile::query()->create([
                    'user_id' => $user->id,
                    'specialty' => 'جراحة الفم والأسنان',
                    'city' => 'جدة',
                    'facility_name' => 'عيادة تجريبية',
                    'scfhs_license_no' => '00-TEST-'.fake()->numerify('####'),
                    'profile_completed_at' => now(),
                ]);
            }
        });
    }

    public function lawyer(): static
    {
        return $this->state(['phone_e164' => null, 'email' => fake()->unique()->userName().'@dentlegal.test', 'password' => 'password-123'])
            ->afterCreating(fn (User $user) => $user->assignRole(Role::Lawyer->value));
    }

    public function disabled(): static
    {
        return $this->state(['status' => UserStatus::Disabled]);
    }
}
