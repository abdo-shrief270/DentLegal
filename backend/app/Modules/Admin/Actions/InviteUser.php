<?php

declare(strict_types=1);

namespace App\Modules\Admin\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\Invitation;
use App\Modules\Identity\Models\User;
use App\Support\Api\ApiException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * «دعوة طبيب» / «دعوة محامٍ» (PROJECT1_PLAN.md S7.1). Dentists are invited by phone (they sign in with an OTP and get an
 * SMS with the download link); lawyers by email, with a one-time link to set a password and enrol two-factor.
 */
final class InviteUser
{
    public function __construct(private readonly SendInvitation $send, private readonly AuditLogger $audit) {}

    /** @param array{name: string, phone_e164: string, specialty?: string|null, city?: string|null} $data */
    public function dentist(User $admin, array $data): Invitation
    {
        if (User::query()->withTrashed()->where('phone_e164', $data['phone_e164'])->exists()) {
            throw new ApiException(409, 'already_registered', 'هذا الرقم مسجّل لحساب قائم.');
        }

        return $this->create($admin, [
            'role' => Role::Dentist, 'name' => $data['name'], 'phone_e164' => $data['phone_e164'],
            'expires_at' => now()->addDays((int) config('dentlegal.invitations.dentist_days')),
            'meta' => ['specialty' => $data['specialty'] ?? null, 'city' => $data['city'] ?? null],
        ], null);
    }

    /** @param array{name: string, email: string, license_no: string, firm_name?: string|null} $data */
    public function lawyer(User $admin, array $data): Invitation
    {
        $email = mb_strtolower($data['email']);
        if (User::query()->withTrashed()->where('email', $email)->exists()) {
            throw new ApiException(409, 'already_registered', 'هذا البريد مسجّل لحساب قائم.');
        }
        if (Invitation::query()->open()->where('email', $email)->exists()) {
            throw new ApiException(409, 'already_invited', 'توجد دعوة سارية لهذا البريد. استخدم «إعادة إرسال الدعوة».');
        }
        $token = Str::random(48);

        return $this->create($admin, [
            'role' => Role::Lawyer, 'name' => $data['name'], 'email' => $email, 'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays((int) config('dentlegal.invitations.dashboard_days')),
            'meta' => ['license_no' => $data['license_no'], 'firm_name' => $data['firm_name'] ?? null],
        ], $token);
    }

    /** @param array<string, mixed> $attributes */
    private function create(User $admin, array $attributes, ?string $token): Invitation
    {
        try {
            $invitation = DB::transaction(function () use ($admin, $attributes): Invitation {
                $invitation = Invitation::query()->create([...$attributes, 'invited_by' => $admin->id]);
                $this->audit->record('invitation.created', $admin, $invitation, ['role' => $invitation->role->value]);

                return $invitation;
            });
        } catch (UniqueConstraintViolationException) {
            throw new ApiException(409, 'already_invited', 'توجد دعوة سارية لهذا الرقم. استخدم «إعادة إرسال الدعوة».');
        }
        ($this->send)($invitation, $token);

        return $invitation;
    }
}
