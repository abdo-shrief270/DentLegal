<?php

declare(strict_types=1);

namespace App\Modules\Identity\Sms;

/** Port for the Saudi SMS provider (D13). Adapters: log (dev/staging), provider HTTP (prod). */
interface SmsGateway
{
    public function send(string $phoneE164, string $message): void;
}
