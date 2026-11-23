<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Invitation;
use App\Modules\Identity\Models\OtpCode;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Sms\SmsGateway;
use App\Support\Api\ApiException;
use Illuminate\Support\Facades\RateLimiter;

/** POST /auth/otp/request (PROJECT1_PLAN.md §4.3, S5.1). Only invited or active dentists get a code. */
final class RequestOtp
{
    public function __construct(private readonly SmsGateway $sms, private readonly AuditLogger $audit) {}

    /** @return array{resend_after_s: int, expires_in_s: int} */
    public function __invoke(string $phone, ?string $ip): array
    {
        $cfg = config('dentlegal.otp');

        $this->throttle('otp:resend:'.$phone, 1, $cfg['resend_after_seconds']);
        $this->throttle('otp:phone:'.$phone, $cfg['per_phone_per_hour'], 3600);
        $this->throttle('otp:ip:'.$ip, $cfg['per_ip_per_hour'], 3600);

        $user = User::query()->where('phone_e164', $phone)->first();
        if ($user !== null && $user->status === UserStatus::Disabled) {
            throw ApiException::forbidden('account_disabled', 'تم إيقاف هذا الحساب. تواصل مع إدارة المنصة.');
        }
        $allowed = ($user !== null && $user->status === UserStatus::Active && $user->hasRole(Role::Dentist->value))
            || Invitation::query()->open()->where('role', Role::Dentist->value)->where('phone_e164', $phone)->exists();
        if (! $allowed) {
            throw ApiException::forbidden('invitation_required', 'هذا الرقم غير مدعو للتجربة. تواصل مع إدارة المنصة.');
        }

        $code = $this->isReviewNumber($phone)
            ? (string) $cfg['review_code']
            : str_pad((string) random_int(0, 10 ** $cfg['length'] - 1), $cfg['length'], '0', STR_PAD_LEFT);

        // A new code replaces any earlier unused one.
        OtpCode::query()->where('phone_e164', $phone)->whereNull('consumed_at')->update(['consumed_at' => now()]);
        OtpCode::query()->create([
            'phone_e164' => $phone,
            'code_hash' => OtpCode::hashFor($phone, $code),
            'expires_at' => now()->addSeconds($cfg['ttl_seconds']),
            'ip' => $ip,
        ]);

        if (! $this->isReviewNumber($phone)) {
            $this->sms->send($phone, "رمز الدخول إلى DentLegal: {$code}. صالح لمدة 5 دقائق. لا تشاركه مع أحد.");
        }
        $this->audit->record('auth.otp_requested', $user, $user, ['review_number' => $this->isReviewNumber($phone)]);

        return ['resend_after_s' => $cfg['resend_after_seconds'], 'expires_in_s' => $cfg['ttl_seconds']];
    }

    private function throttle(string $key, int $max, int $decaySeconds): void
    {
        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw ApiException::rateLimited(RateLimiter::availableIn($key));
        }
        RateLimiter::hit($key, $decaySeconds);
    }

    private function isReviewNumber(string $phone): bool
    {
        $review = config('dentlegal.otp.review_phone');

        return $review !== null && $review !== '' && config('dentlegal.otp.review_code') && $review === $phone;
    }
}
