<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Enums\ConsentType;
use App\Modules\Identity\Models\User;
use App\Support\Api\ApiException;
use Illuminate\Support\Facades\DB;

/** POST /me/consents. Consents are append-only; granting the current version again is a no-op. */
final class RecordConsents
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** @param list<array{type: string, version: string}> $consents */
    public function __invoke(User $user, array $consents, ?string $ip): void
    {
        DB::transaction(function () use ($user, $consents, $ip): void {
            foreach ($consents as $consent) {
                $type = ConsentType::from($consent['type']);
                $current = config('dentlegal.consent_versions.'.$type->value);
                if ($consent['version'] !== $current) {
                    throw new ApiException(422, 'consent_version_outdated', 'نص الموافقة تغيّر. حدّث التطبيق ثم أعد المحاولة.', ['type' => $type->value, 'current_version' => $current]);
                }
                if ($user->hasConsent($type)) {
                    continue;
                }
                $user->consents()->create(['type' => $type, 'version' => $current, 'granted_at' => now(), 'ip' => $ip]);
                $this->audit->record('consent.granted', $user, $user, ['type' => $type->value, 'version' => $current]);
            }
        });
    }
}
