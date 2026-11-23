<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Push;

use Illuminate\Support\Facades\Log;

/** Development and staging until the Firebase project exists: logs the message, never the device token. */
final class LogPushGateway implements PushGateway
{
    public function send(string $deviceToken, string $title, string $body, array $data): PushResult
    {
        Log::info('push', ['title' => $title, 'body' => $body, 'data' => $data, 'token_suffix' => substr($deviceToken, -6)]);

        return PushResult::Sent;
    }
}
