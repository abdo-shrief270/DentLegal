<?php

declare(strict_types=1);

namespace App\Modules\Cases\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Cases\Enums\CaseStatus;
use App\Modules\Cases\Models\DentalCase;
use App\Support\Api\ApiException;
use Illuminate\Support\Facades\DB;

/** The single place where a case changes status. Row-locked, so two requests can't both move the same case. */
final class TransitionCase
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** @param array<string, mixed> $metadata */
    public function __invoke(DentalCase $case, CaseStatus $to, array $metadata = [], ?string $failureCode = null): DentalCase
    {
        return DB::transaction(function () use ($case, $to, $metadata, $failureCode): DentalCase {
            /** @var DentalCase $locked */
            $locked = DentalCase::query()->whereKey($case->getKey())->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw ApiException::invalidState(details: ['from' => $from->value, 'to' => $to->value]);
            }

            $locked->status = $to;
            $locked->failure_code = $to === CaseStatus::Failed ? $failureCode : null;
            match ($to) {
                CaseStatus::LawyerReview => $locked->submitted_for_review_at = now(),
                CaseStatus::Approved => $locked->approved_at = now(),
                default => null,
            };
            $locked->save();
            $this->audit->record('case.status_changed', null, $locked, [...$metadata, 'from' => $from->value, 'to' => $to->value]);

            return $locked;
        });
    }
}
