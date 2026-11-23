<?php

declare(strict_types=1);

namespace App\Modules\Review\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Models\Draft;
use App\Support\Api\ApiException;

final class ReviewGuard
{
    /** Edits, notes and approval belong to the lawyer who claimed the case, while it is in review. */
    public static function assertClaimedBy(Draft $draft, User $lawyer): void
    {
        if ($draft->status !== 'in_review') {
            throw ApiException::invalidState('القضية ليست قيد المراجعة.');
        }
        if ($draft->reviewer_id !== $lawyer->id) {
            throw ApiException::forbidden('not_claimed_by_you', 'استلم القضية أولًا لتتمكن من تعديلها.');
        }
    }
}
