<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Push;

/** Port for push delivery (PROJECT1_PLAN.md §2, D: FCM/APNs). Messages are content-free: generic text and ids only. */
interface PushGateway
{
    /** @param array<string, string> $data */
    public function send(string $deviceToken, string $title, string $body, array $data): PushResult;
}
