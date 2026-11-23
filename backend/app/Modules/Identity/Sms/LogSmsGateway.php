<?php

declare(strict_types=1);

namespace App\Modules\Identity\Sms;

use Illuminate\Support\Facades\Log;

/** Writes messages to the `sms` log channel instead of sending them. Never enabled in production. */
final class LogSmsGateway implements SmsGateway
{
    public function send(string $phoneE164, string $message): void
    {
        Log::channel(config('dentlegal.sms.log_channel'))->info('sms', ['to' => $phoneE164, 'message' => $message]);
    }
}
