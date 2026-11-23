<?php

declare(strict_types=1);

namespace App\Modules\Identity\Enums;

/** Consents of PROJECT1_PLAN.md §9.2. Versions come from config('dentlegal.consent_versions'). */
enum ConsentType: string
{
    case Terms = 'terms';
    case Privacy = 'privacy';
    case AiProcessing = 'ai_processing';
    case LawyerAccess = 'lawyer_access';

    /**
     * Required at profile completion.
     *
     * @return list<self>
     */
    public static function onboarding(): array
    {
        return [self::Terms, self::Privacy, self::AiProcessing];
    }
}
