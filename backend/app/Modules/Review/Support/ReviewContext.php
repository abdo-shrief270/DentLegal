<?php

declare(strict_types=1);

namespace App\Modules\Review\Support;

use App\Modules\Cases\Models\DentalCase;
use App\Modules\Knowledge\Models\KnowledgeChunk;
use App\Modules\Pipeline\Models\Draft;
use App\Modules\Pipeline\Support\CaseBundle;
use Illuminate\Support\Facades\DB;

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
        $sources = [];
        if ($runId !== null) {
            $rows = DB::table('ai_run_sources')->where('ai_run_id', $runId)->whereNotNull('local_ref')->orderBy('local_ref')->get();
            $chunks = KnowledgeChunk::query()->with('version.source')->whereIn('id', $rows->pluck('chunk_id'))->get()->keyBy('id');
            foreach ($rows as $row) {
                $chunk = $chunks->get($row->chunk_id);
                if ($chunk === null) {
                    continue;
                }
                $path = array_filter(explode(',', trim((string) $chunk->getAttribute('heading_path'), '{}')));
                $sources[(string) $row->local_ref] = [
                    'chunk_id' => $chunk->id,
                    'law' => (string) ($chunk->version->source->title_ar ?? ''),
                    'label' => $chunk->label(),
                    'title' => trim((string) (end($path) ?: ''), '"'),
                    'text' => $chunk->display_text,
                    'score' => (float) $row->rerank_score,
                ];
            }
            uksort($sources, fn (string $a, string $b): int => (int) substr($a, 1) <=> (int) substr($b, 1));
        }
        $documents = [];
        foreach (CaseBundle::documents($case) as $i => $doc) {
            $documents['D'.($i + 1)] = $doc;
        }

        return new self($sources, $documents);
    }
}
