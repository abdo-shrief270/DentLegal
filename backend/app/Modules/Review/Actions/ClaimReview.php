<?php

declare(strict_types=1);

namespace App\Modules\Review\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Models\Draft;
use App\Support\Api\ApiException;
use Illuminate\Support\Facades\DB;

/** «استلام القضية»: one conditional UPDATE, so two lawyers claiming at once can't both win (409 `already_claimed`). */
final class ClaimReview
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(User $lawyer, Draft $draft): Draft
    {
        $won = DB::table('drafts')->where('id', $draft->id)->where('status', 'in_review')->whereNull('reviewer_id')
            ->update(['reviewer_id' => $lawyer->id, 'claimed_at' => now(), 'updated_at' => now()]);
        $draft->refresh();
        if ($won === 0) {
            if ($draft->status === 'in_review' && $draft->reviewer_id === $lawyer->id) {
                return $draft; // a double tap by the same lawyer
            }
            throw $draft->status === 'in_review'
                ? new ApiException(409, 'already_claimed', 'استلم محامٍ آخر هذه القضية.')
                : ApiException::invalidState('القضية ليست بانتظار المراجعة.');
        }
        $this->audit->record('review.claimed', $lawyer, $draft, ['case_id' => $draft->case_id]);

        return $draft;
    }
}
