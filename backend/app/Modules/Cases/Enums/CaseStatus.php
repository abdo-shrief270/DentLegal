<?php

declare(strict_types=1);

namespace App\Modules\Cases\Enums;

/**
 * Platform status of a case (PROJECT1_PLAN.md §1.1, §4.4). The allowed transitions are the only way
 * a case changes status; anything else is a 409 invalid_state.
 */
enum CaseStatus: string
{
    case Draft = 'draft';
    case Analyzing = 'analyzing';
    case AiReady = 'ai_ready';
    case LawyerReview = 'lawyer_review';
    case Approved = 'approved';
    case Failed = 'failed';

    /** @return list<self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::Analyzing],
            self::Analyzing => [self::AiReady, self::Failed],
            self::Failed => [self::Analyzing],
            self::AiReady => [self::LawyerReview, self::Analyzing],
            self::LawyerReview => [self::Approved],
            self::Approved => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    /** Documents can be added or removed only before analysis starts. */
    public function acceptsDocuments(): bool
    {
        return $this === self::Draft;
    }
}
