<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Console;

use Illuminate\Console\Command;

/**
 * `php artisan security:check` (PROJECT1_PLAN.md S9.3): fails when the running configuration is unsafe for production.
 * Run it in the deploy smoke check; warnings are things still pending a decision (SMS, push, email providers).
 */
final class SecurityCheck extends Command
{
    protected $signature = 'security:check';

    protected $description = 'Check the configuration for production safety';

    public function handle(): int
    {
        $failures = [];
        $warnings = [];
        $fail = function (bool $bad, string $message) use (&$failures): void {
            if ($bad) {
                $failures[] = $message;
            }
        };
        $warn = function (bool $bad, string $message) use (&$warnings): void {
            if ($bad) {
                $warnings[] = $message;
            }
        };

        $fail(! app()->environment('production'), 'APP_ENV is not production');
        $fail((bool) config('app.debug'), 'APP_DEBUG is on (stack traces would reach clients)');
        $fail(! str_starts_with((string) config('app.url'), 'https://'), 'APP_URL is not https');
        $fail(strlen((string) config('app.key')) < 32, 'APP_KEY is missing');

        $master = base64_decode((string) config('dentlegal.documents.master_key'), true);
        $fail($master === false || strlen($master) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES, 'DOCUMENT_MASTER_KEY is not 32 random bytes in base64');
        $token = (string) config('dentlegal.ai_service.token');
        $fail(strlen($token) < 32, 'AI_SERVICE_TOKEN is missing or shorter than 32 characters');
        $fail(! str_starts_with((string) config('dentlegal.ai_service.url'), 'http'), 'AI_SERVICE_URL is not set');

        $fail(config('session.secure') !== true, 'SESSION_SECURE_COOKIE is not true');
        $fail(config('session.http_only') !== true, 'SESSION_HTTP_ONLY is not true');
        $fail((int) config('session.lifetime') > 120, 'SESSION_LIFETIME is above the 2 h idle timeout');
        $fail(! str_starts_with((string) config('dentlegal.invitations.dashboard_url'), 'https://'), 'DASHBOARD_URL is not https');
        $stateful = (array) config('sanctum.stateful');
        $fail(collect($stateful)->contains(fn ($d) => str_contains((string) $d, 'localhost') || str_contains((string) $d, '127.0.0.1')), 'SANCTUM_STATEFUL_DOMAINS still lists localhost');
        $fail(in_array('*', (array) config('cors.allowed_origins'), true), 'CORS allows any origin');

        $fail(config('logging.channels.single.level') === 'debug' || config('logging.channels.stderr.level') === 'debug', 'LOG_LEVEL is debug (request details in logs)');
        $warn(config('dentlegal.otp.review_phone') !== null, 'OTP_REVIEW_PHONE is set: the store-review number signs in with a fixed code; unset it after review');
        $warn(config('dentlegal.sms.driver') === 'log', 'SMS_DRIVER is log: codes are not sent (D13)');
        $warn(config('dentlegal.push.driver') === 'log', 'PUSH_DRIVER is log: approvals are not pushed (Firebase)');
        $warn(config('mail.default') === 'log', 'MAIL_MAILER is log: lawyer invites are not emailed (D17)');

        foreach ($failures as $f) {
            $this->error("FAIL  {$f}");
        }
        foreach ($warnings as $w) {
            $this->warn("WARN  {$w}");
        }
        if ($failures === []) {
            $this->info('Configuration is safe for production'.($warnings ? ' (with warnings)' : '').'.');
        }

        return $failures === [] ? self::SUCCESS : self::FAILURE;
    }
}
