<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Listeners;

use App\Modules\Notifications\Jobs\SendDraftApprovedPush;
use App\Modules\Review\Events\DraftApproved;

final class NotifyDentistOfApproval
{
    public function handle(DraftApproved $event): void
    {
        SendDraftApprovedPush::dispatch($event->caseId);
    }
}
