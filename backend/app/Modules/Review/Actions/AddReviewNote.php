<?php

declare(strict_types=1);

namespace App\Modules\Review\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Models\Draft;
use App\Modules\Review\Models\ReviewNote;

/** Internal note on a review; never shown to the dentist. Allowed for the claiming lawyer while in review. */
final class AddReviewNote
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(User $lawyer, Draft $draft, string $text): ReviewNote
    {
        ReviewGuard::assertClaimedBy($draft, $lawyer);
        $note = ReviewNote::query()->create(['draft_id' => $draft->id, 'author_id' => $lawyer->id, 'text' => $text]);
        $this->audit->record('review.note_added', $lawyer, $draft);

        return $note;
    }
}
