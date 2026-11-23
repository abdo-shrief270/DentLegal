<?php

declare(strict_types=1);

namespace App\Modules\Cases\Http\Resources;

use App\Modules\Cases\Models\DentalCase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DentalCase */
final class CaseResource extends JsonResource
{
    /** @return array<int|string, mixed> */
    public function toArray(Request $request): array
    {
        $detail = $request->routeIs('cases.show', 'cases.store');

        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'case_type' => $this->case_type->value,
            'claim_type' => $this->claim_type->value,
            'authority' => $this->authority,
            'status' => $this->status->value,
            'failure_code' => $this->when($this->failure_code !== null, $this->failure_code),
            'received_on' => $this->received_on->toDateString(),
            'created_at' => $this->created_at->toIso8601ZuluString(),
            'updated_at' => $this->updated_at->toIso8601ZuluString(),
            'submitted_for_review_at' => $this->submitted_for_review_at?->toIso8601ZuluString(),
            'approved_at' => $this->approved_at?->toIso8601ZuluString(),
            $this->mergeWhen($detail, fn (): array => [
                'complaint_no' => $this->complaint_no,
                'procedure' => $this->procedure,
                'procedure_date' => $this->procedure_date->toDateString(),
                'patient_code' => $this->patient_code,
                'facts' => $this->facts_masked,
                'masked_entities' => (object) $this->facts_entity_counts,
                'actions' => [
                    'can_upload_documents' => $this->status->acceptsDocuments(),
                    'next_statuses' => array_map(fn ($s) => $s->value, $this->status->allowedNext()),
                ],
            ]),
        ];
    }
}
