<?php

declare(strict_types=1);

namespace App\Modules\Admin\Console;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\Invitation;
use App\Modules\Identity\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * The first admin on a new server (infra/prod/README.md): admins are otherwise invited from the admin portal, which
 * needs an admin. Prints the one-time link (no email needed); the admin sets a password and two-factor through it.
 */
final class InviteAdmin extends Command
{
    protected $signature = 'dentlegal:invite-admin {email} {name}';

    protected $description = 'Create a one-time invitation link for an admin account';

    public function handle(AuditLogger $audit): int
    {
        $email = mb_strtolower((string) $this->argument('email'));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('Not a valid email address.');

            return self::FAILURE;
        }
        if (User::query()->withTrashed()->where('email', $email)->exists()) {
            $this->error('An account with this email already exists.');

            return self::FAILURE;
        }
        Invitation::query()->open()->where('email', $email)->update(['expires_at' => now()]);

        $token = Str::random(48);
        $invitation = Invitation::query()->create([
            'role' => Role::Admin, 'name' => (string) $this->argument('name'), 'email' => $email, 'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays((int) config('dentlegal.invitations.dashboard_days')), 'meta' => [], 'invited_by' => null,
        ]);
        $audit->record('invitation.created', null, $invitation, ['role' => Role::Admin->value, 'via' => 'console']);

        $this->line(rtrim((string) config('dentlegal.invitations.dashboard_url'), '/').'/invite/'.$token);
        $this->info('Send this link to the admin privately. It works once and expires in '.config('dentlegal.invitations.dashboard_days').' days.');

        return self::SUCCESS;
    }
}
