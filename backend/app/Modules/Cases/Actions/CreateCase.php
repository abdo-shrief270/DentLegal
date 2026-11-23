<?php

declare(strict_types=1);

namespace App\Modules\Cases\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Cases\Models\DentalCase;
use App\Modules\Identity\Models\User;
use App\Support\Masking\PatternMasker;
use Illuminate\Support\Facades\DB;

/**
 * POST /cases. The typed facts are pattern-masked before they are stored (IDs, phones, emails, IBANs),
 * so none of those identifiers ever reach the database. Names are masked by ai-svc in the pipeline.
 */
final class CreateCase
{
    public function __construct(
        private readonly NextCaseCode $nextCode,
        private readonly PatternMasker $masker,
        private readonly AuditLogger $audit,
    ) {}

    /** @param array<string, mixed> $data validated StoreCaseRequest data */
    public function __invoke(User $dentist, array $data): DentalCase
    {
        $masked = $this->masker->mask((string) $data['facts']);
        unset($data['facts']);

        return DB::transaction(function () use ($dentist, $data, $masked): DentalCase {
            $case = DentalCase::query()->create([
                ...$data,
                'code' => ($this->nextCode)(),
                'dentist_id' => $dentist->id,
                'facts_masked' => $masked->text,
                'facts_entity_counts' => $masked->entityCounts,
            ]);
            $this->audit->record('case.created', $dentist, $case, ['masked_entities' => $masked->entityCounts]);

            return $case;
        });
    }
}
