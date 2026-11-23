<?php

declare(strict_types=1);

namespace App\Modules\Admin\Actions;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\Invitation;
use App\Modules\Identity\Sms\SmsGateway;
use Illuminate\Support\Facades\Mail;

/**
 * Delivers an invitation: an SMS for dentists (D13 gateway), an email with the one-time link for lawyers and admins
 * (D17 provider; the log mailer until it is chosen). Messages carry no case data.
 */
final class SendInvitation
{
    public function __construct(private readonly SmsGateway $sms) {}

    public function __invoke(Invitation $invitation, ?string $token): void
    {
        if ($invitation->role === Role::Dentist) {
            $this->sms->send((string) $invitation->phone_e164, sprintf(
                'مرحبًا %s، تمت دعوتك لتجربة DentLegal. حمّل التطبيق: %s ثم سجّل الدخول برقم جوالك.',
                $invitation->name ?? '', config('dentlegal.invitations.app_download_url'),
            ));
        } elseif ($token !== null) {
            $link = rtrim((string) config('dentlegal.invitations.dashboard_url'), '/').'/invite/'.$token;
            Mail::raw(
                "مرحبًا {$invitation->name}،\n\nتمت دعوتك إلى لوحة DentLegal. لتعيين كلمة المرور وتفعيل التحقق الثنائي افتح الرابط التالي (صالح ".
                config('dentlegal.invitations.dashboard_days')." أيام):\n{$link}\n\nإن لم تكن تتوقع هذه الرسالة فتجاهلها.",
                fn ($m) => $m->to((string) $invitation->email)->subject('دعوة إلى لوحة DentLegal'),
            );
        }
        $invitation->forceFill(['sent_count' => $invitation->sent_count + 1, 'last_sent_at' => now()])->save();
    }
}
