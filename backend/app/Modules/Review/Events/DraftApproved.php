<?php

declare(strict_types=1);

namespace App\Modules\Review\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** Raised after commit when a lawyer approves a draft; the push notification to the dentist listens to it (S5.5). */
final class DraftApproved
{
    use Dispatchable;

    public function __construct(public readonly string $draftId, public readonly string $caseId, public readonly string $dentistId) {}
}
