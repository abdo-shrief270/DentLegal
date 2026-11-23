<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Resources;

use App\Modules\Identity\Enums\ConsentType;
use App\Modules\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class MeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $profile = $this->dentistProfile;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone_e164,
            'role' => $this->primaryRole()?->value,
            'needs_profile' => $this->needsProfile(),
            'profile' => $profile === null ? null : [
                'specialty' => $profile->specialty,
                'city' => $profile->city,
                'facility_name' => $profile->facility_name,
                'scfhs_license_no' => $profile->scfhs_license_no,
            ],
            'consents' => collect(ConsentType::cases())->mapWithKeys(fn (ConsentType $t): array => [
                $t->value => ['granted' => $this->hasConsent($t), 'current_version' => config('dentlegal.consent_versions.'.$t->value)],
            ]),
        ];
    }
}
