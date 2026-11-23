<?php

declare(strict_types=1);

namespace App\Modules\Admin\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use App\Support\Api\ApiException;
use Illuminate\Support\Facades\DB;

/** «إيقاف / تفعيل»: deactivation revokes every API token and dashboard session at once (S7.1). */
final class SetUserActive
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(User $admin, User $user, bool $active): User
    {
        if ($user->id === $admin->id) {
            throw ApiException::forbidden('cannot_change_self', 'لا يمكنك إيقاف حسابك.');
        }
        if (! in_array($user->status, [UserStatus::Active, UserStatus::Disabled], true)) {
            throw ApiException::invalidState('لا يمكن تغيير حالة هذا الحساب.');
        }

        return DB::transaction(function () use ($admin, $user, $active): User {
            $user->forceFill(['status' => $active ? UserStatus::Active : UserStatus::Disabled])->save();
            if (! $active) {
                $user->tokens()->delete();
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            $this->audit->record($active ? 'user.activated' : 'user.deactivated', $admin, $user);

            return $user;
        });
    }
}
