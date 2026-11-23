<?php

declare(strict_types=1);

namespace App\Modules\Review\Support;

use App\Modules\Cases\Models\DentalCase;
use App\Modules\Pipeline\Models\Draft;
use App\Modules\Pipeline\Support\CaseBundle;
use App\Modules\Pipeline\Support\RunSources;

/**
 * What the draft's citations can point at: the S refs of the AI run behind the latest AI version (same refs the
 * model saw) and the D refs of the case documents, in the order they were sent (CaseBundle).
 */
final class ReviewContext
{
    /**
     * @param  array<string, array{chunk_id: int, law: string, label: string, title: string, text: string, score: float}>  $sources
     * @param  array<string, array{document_id: string, name: string, kind: string, ocr_confidence: float|null, pages: list<array{page_no: int, document_page_id: string, text: string}>}>  $documents
     */
    private function __construct(public readonly array $sources, public readonly array $documents) {}

    public static function for(Draft $draft, DentalCase $case): self
    {
        $runId = $draft->versions()->where('author_type', 'ai')->orderByDesc('version_no')->value('ai_run_id');
        $sources = RunSources::for($runId === null ? null : (int) $runId);
        $documents = [];
        foreach (CaseBundle::documents($case) as $i => $doc) {
            $documents['D'.($i + 1)] = $doc;
        }

        return new self($sources, $documents);
    }
}
