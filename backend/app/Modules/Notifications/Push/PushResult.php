<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Push;

enum PushResult: string
{
    case Sent = 'sent';
    /** The token is no longer valid (app uninstalled, token rotated): stop sending to it. */
    case InvalidToken = 'invalid_token';
    case Failed = 'failed';
}
