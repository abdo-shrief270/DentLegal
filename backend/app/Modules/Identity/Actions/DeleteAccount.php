<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * DELETE /me (store requirement). Signs the dentist out everywhere and soft-deletes the account now;
 * PurgeExpiredData removes cases and documents within 30 days (PROJECT1_PLAN.md §9.7).
 */
final class DeleteAccount
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->audit->record('account.deletion_requested', $user, $user);
            $user->tokens()->delete();
            $user->devices()->delete();
            $user->forceFill(['status' => UserStatus::Deleted, 'phone_e164' => null, 'email' => null])->save();
            $user->delete();
        });
    }
}
