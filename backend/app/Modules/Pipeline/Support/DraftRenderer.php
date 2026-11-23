<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Support;

use App\Modules\Cases\Models\DentalCase;

/**
 * Fills the placeholders when a draft is shown (PROJECT1_PLAN.md §5.8): the model never sees the dentist's identity,
 * and the patient appears by code. The stored draft keeps the placeholders.
 */
final class DraftRenderer
{
    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public static function render(array $content, DentalCase $case): array
    {
        $profile = $case->dentist->dentistProfile;
        $map = [
            '{{DENTIST_NAME}}' => $case->dentist->name ?? 'الممارس الصحي',
            '{{DENTIST_LICENSE}}' => $profile->scfhs_license_no ?? '—',
            '[PATIENT]' => 'المريض (الرمز '.$case->patient_code.')',
        ];
        array_walk_recursive($content, function (mixed &$value) use ($map): void {
            if (is_string($value)) {
                $value = strtr($value, $map);
            }
        });

        return $content;
    }
}
