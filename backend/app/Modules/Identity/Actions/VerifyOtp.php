<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Enums\Platform;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Invitation;
use App\Modules\Identity\Models\OtpCode;
use App\Modules\Identity\Models\User;
use App\Support\Api\ApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/** POST /auth/otp/verify: checks the code, activates an invitation on first login, registers the device, issues a token. */
final class VerifyOtp
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{platform: string, name?: string|null, app_version?: string|null}  $device
     * @return array{token: string, user: User}
     */
    public function __invoke(string $phone, string $code, array $device): array
    {
        $key = 'otp:verify:'.$phone;
        if (RateLimiter::tooManyAttempts($key, config('dentlegal.otp.verify_per_phone_per_hour'))) {
            throw ApiException::rateLimited(RateLimiter::availableIn($key));
        }
        RateLimiter::hit($key, 3600);

        $otp = $this->checkCode($phone, $code);

        return DB::transaction(function () use ($otp, $phone, $device): array {
            $user = $this->resolveUser($phone);
            $otp->update(['consumed_at' => now()]);

            $token = $user->createToken(
                $device['name'] ?? $device['platform'],
                config('dentlegal.tokens.abilities'),
                now()->addDays(config('dentlegal.tokens.ttl_days')),
            );
            $user->devices()->create([
                'token_id' => $token->accessToken->getKey(),
                'platform' => Platform::from($device['platform']),
                'name' => $device['name'] ?? null,
                'app_version' => $device['app_version'] ?? null,
                'last_seen_at' => now(),
            ]);
            $user->forceFill(['last_login_at' => now()])->save();
            $this->audit->record('auth.otp_verified', $user, $user, ['platform' => $device['platform']]);

            return ['token' => $token->plainTextToken, 'user' => $user];
        });
    }

    /**
     * Checks the code under a row lock. A wrong code is counted (and the code burnt after the last attempt)
     * in its own committed transaction, then reported.
     */
    private function checkCode(string $phone, string $code): OtpCode
    {
        $result = DB::transaction(function () use ($phone, $code): OtpCode|int|null {
            /** @var OtpCode|null $otp */
            $otp = OtpCode::query()
                ->where('phone_e164', $phone)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->latest('created_at')
                ->lockForUpdate()
                ->first();

            if ($otp === null) {
                return null;
            }
            if (hash_equals($otp->code_hash, OtpCode::hashFor($phone, $code))) {
                return $otp;
            }

            $otp->attempts++;
            if ($otp->attempts >= config('dentlegal.otp.max_attempts')) {
                $otp->consumed_at = now();
            }
            $otp->save();

            return max(0, config('dentlegal.otp.max_attempts') - $otp->attempts);
        });

        if ($result === null) {
            throw new ApiException(422, 'otp_expired', 'انتهت صلاحية الرمز. اطلب رمزًا جديدًا.');
        }
        if (is_int($result)) {
            $this->audit->record('auth.otp_failed', null, null, ['attempts_left' => $result]);

            throw new ApiException(422, 'otp_invalid', 'الرمز غير صحيح.', ['attempts_left' => $result]);
        }

        return $result;
    }

    private function resolveUser(string $phone): User
    {
        $user = User::query()->where('phone_e164', $phone)->first();
        if ($user !== null) {
            if ($user->status !== UserStatus::Active) {
                throw ApiException::forbidden('account_disabled', 'تم إيقاف هذا الحساب. تواصل مع إدارة المنصة.');
            }

            return $user;
        }

        /** @var Invitation|null $invitation */
        $invitation = Invitation::query()->open()->where('role', Role::Dentist->value)->where('phone_e164', $phone)->lockForUpdate()->first();
        if ($invitation === null) {
            throw ApiException::forbidden('invitation_required', 'هذا الرقم غير مدعو للتجربة. تواصل مع إدارة المنصة.');
        }

        $user = User::query()->create(['name' => $invitation->name, 'phone_e164' => $phone, 'status' => UserStatus::Active]);
        $user->assignRole(Role::Dentist->value);
        $invitation->update(['accepted_at' => now()]);
        $this->audit->record('invitation.accepted', $user, $invitation);

        return $user;
    }
}
