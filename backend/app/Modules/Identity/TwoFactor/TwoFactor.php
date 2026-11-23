<?php

declare(strict_types=1);

namespace App\Modules\Identity\TwoFactor;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Models\User;
use App\Support\Api\ApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Enrolment and checking of dashboard two-factor (PROJECT1_PLAN.md S5.7). The secret is stored encrypted; recovery
 * codes are shown once and stored as hashes, each usable once.
 */
final class TwoFactor
{
    public const RECOVERY_CODES = 8;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * A new secret and recovery codes, shown once, while two-factor is not yet confirmed (calling again replaces them).
     * Nothing is enforced until confirm() succeeds with a code from the app.
     *
     * @return array{secret: string, otpauth_uri: string, recovery_codes: list<string>}
     */
    public function begin(User $user): array
    {
        if ($user->two_factor_confirmed_at !== null) {
            throw new ApiException(409, 'two_factor_enabled', 'التحقق الثنائي مفعّل مسبقًا.');
        }
        $secret = Totp::newSecret();
        $codes = array_map(fn (): string => Str::upper(Str::random(5).'-'.Str::random(5)), range(1, self::RECOVERY_CODES));
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => array_map(self::hash(...), $codes),
            'two_factor_last_step' => null,
        ])->save();

        return ['secret' => $secret, 'otpauth_uri' => Totp::uri($secret, (string) $user->email, (string) config('app.name')), 'recovery_codes' => $codes];
    }

    public function confirm(User $user, string $code): void
    {
        DB::transaction(function () use ($user, $code): void {
            /** @var User $locked */
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($locked->two_factor_confirmed_at !== null) {
                throw new ApiException(409, 'two_factor_enabled', 'التحقق الثنائي مفعّل مسبقًا.');
            }
            if ($locked->two_factor_secret === null) {
                throw new ApiException(409, 'two_factor_not_started', 'ابدأ تفعيل التحقق الثنائي أولًا.');
            }
            $step = Totp::verify($locked->two_factor_secret, $code);
            if ($step === null) {
                throw self::invalidCode();
            }
            $locked->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_last_step' => $step])->save();
            $user->setRawAttributes($locked->getAttributes(), true);
            $this->audit->record('two_factor.enabled', $locked, $locked);
        });
    }

    /** A TOTP code or a recovery code (used up). Throws on a wrong code. */
    public function check(User $user, ?string $code, ?string $recoveryCode): void
    {
        DB::transaction(function () use ($user, $code, $recoveryCode): void {
            /** @var User $locked */
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($locked->two_factor_confirmed_at === null || $locked->two_factor_secret === null) {
                throw new ApiException(409, 'two_factor_not_enabled', 'التحقق الثنائي غير مفعّل.');
            }
            if ($recoveryCode !== null && $recoveryCode !== '') {
                $hashes = $locked->two_factor_recovery_codes ?? [];
                $hash = self::hash($recoveryCode);
                $index = array_search($hash, $hashes, true);
                if ($index === false) {
                    throw self::invalidCode();
                }
                unset($hashes[$index]);
                $locked->forceFill(['two_factor_recovery_codes' => array_values($hashes)])->save();
                $this->audit->record('two_factor.recovery_code_used', $locked, $locked, ['remaining' => count($hashes)]);

                return;
            }
            $step = Totp::verify($locked->two_factor_secret, (string) $code, $locked->two_factor_last_step);
            if ($step === null) {
                throw self::invalidCode();
            }
            $locked->forceFill(['two_factor_last_step' => $step])->save();
        });
    }

    private static function hash(string $recoveryCode): string
    {
        return hash('sha256', Str::upper(trim($recoveryCode)));
    }

    private static function invalidCode(): ApiException
    {
        return new ApiException(422, 'invalid_code', 'رمز التحقق غير صحيح.');
    }
}
