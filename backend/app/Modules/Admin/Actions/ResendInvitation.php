<?php

declare(strict_types=1);

namespace App\Modules\Admin\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\Invitation;
use App\Modules\Identity\Models\User;
use App\Support\Api\ApiException;
use Illuminate\Support\Str;

/** «إعادة إرسال الدعوة»: extends the expiry; a dashboard invite gets a new link and the old one stops working. */
final class ResendInvitation
{
    public function __construct(private readonly SendInvitation $send, private readonly AuditLogger $audit) {}

    public function __invoke(User $admin, Invitation $invitation): Invitation
    {
        if ($invitation->accepted_at !== null || $invitation->revoked_at !== null) {
            throw ApiException::invalidState('هذه الدعوة لم تعد سارية.');
        }
        $cooldown = (int) config('dentlegal.invitations.resend_cooldown_s');
        if ($invitation->last_sent_at !== null && $invitation->last_sent_at->diffInSeconds(now()) < $cooldown) {
            throw ApiException::rateLimited($cooldown - (int) $invitation->last_sent_at->diffInSeconds(now()));
        }
        $token = null;
        $days = (int) config($invitation->role === Role::Dentist ? 'dentlegal.invitations.dentist_days' : 'dentlegal.invitations.dashboard_days');
        $changes = ['expires_at' => now()->addDays($days)];
        if ($invitation->role !== Role::Dentist) {
            $token = Str::random(48);
            $changes['token_hash'] = hash('sha256', $token);
        }
        $invitation->forceFill($changes)->save();
        ($this->send)($invitation, $token);
        $this->audit->record('invitation.resent', $admin, $invitation);

        return $invitation;
    }
}
