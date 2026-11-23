<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

/** PUT /me/profile. The first save completes the profile (needs_profile → false). */
final class CompleteDentistProfile
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** @param array{name: string, specialty: string, city: string, facility_name: string, scfhs_license_no?: string|null} $data */
    public function __invoke(User $user, array $data): User
    {
        DB::transaction(function () use ($user, $data): void {
            $user->update(['name' => $data['name']]);
            $profile = $user->dentistProfile()->firstOrNew();
            $profile->fill([
                'specialty' => $data['specialty'],
                'city' => $data['city'],
                'facility_name' => $data['facility_name'],
                'scfhs_license_no' => $data['scfhs_license_no'] ?? null,
            ]);
            $profile->profile_completed_at ??= now();
            $profile->save();
            $this->audit->record('profile.updated', $user, $user);
        });

        return $user->refresh()->load('dentistProfile');
    }
}
