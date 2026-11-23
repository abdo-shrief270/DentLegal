<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Support;

use App\Modules\Cases\Models\DentalCase;
use App\Modules\Documents\Models\Document;

/** The masked case payload sent to ai-svc (/v1/case/analyze and /v1/case/draft). Only masked text is ever included. */
final class CaseBundle
{
    /** @return array<string, string> */
    public static function case(DentalCase $case): array
    {
        return [
            'case_type' => $case->case_type->value,
            'claim_type' => $case->claim_type->value,
            'authority' => (string) $case->authority,
            'complaint_no' => (string) $case->complaint_no,
            'received_on' => $case->received_on->toDateString(),
            'title' => (string) $case->title,
            'procedure' => (string) $case->procedure,
            'procedure_date' => $case->procedure_date->toDateString(),
            'patient_code' => (string) $case->patient_code,
            'facts' => $case->facts_masked,
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function documents(DentalCase $case): array
    {
        return Document::query()->where('case_id', $case->id)->where('ocr_status', 'done')->orderBy('created_at')->with('pages')->get()
            ->map(fn (Document $d): array => [
                'document_id' => $d->id,
                'kind' => $d->kind->value,
                'name' => $d->original_name,
                'ocr_confidence' => $d->ocr_confidence,
                'pages' => $d->pages->map(fn ($p): array => ['page_no' => $p->page_no, 'document_page_id' => $p->id, 'text' => $p->text_masked])->values()->all(),
            ])->values()->all();
    }
}
