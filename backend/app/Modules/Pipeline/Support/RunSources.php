<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Support;

use App\Modules\Knowledge\Models\KnowledgeChunk;
use Illuminate\Support\Facades\DB;

/** The regulation chunks an AI run used, under the S refs the model saw (ai_run_sources), with their text and score. */
final class RunSources
{
    /** @return array<string, array{chunk_id: int, law: string, label: string, title: string, text: string, score: float}> keyed by ref, S1 first */
    public static function for(?int $runId): array
    {
        if ($runId === null) {
            return [];
        }
        $rows = DB::table('ai_run_sources')->where('ai_run_id', $runId)->whereNotNull('local_ref')->get();
        $chunks = KnowledgeChunk::query()->with('version.source')->whereIn('id', $rows->pluck('chunk_id'))->get()->keyBy('id');
        $sources = [];
        foreach ($rows as $row) {
            $chunk = $chunks->get($row->chunk_id);
            if ($chunk === null) {
                continue;
            }
            $sources[(string) $row->local_ref] = [
                'chunk_id' => $chunk->id,
                'law' => (string) ($chunk->version->source->title_ar ?? ''),
                'label' => $chunk->label(),
                'title' => self::title($chunk),
                'text' => $chunk->display_text,
                'score' => (float) $row->rerank_score,
            ];
        }
        uksort($sources, fn (string $a, string $b): int => (int) substr($a, 1) <=> (int) substr($b, 1));

        return $sources;
    }

    /** The last heading above the chunk («واجبات الممارس الصحي»), from the Postgres text[] heading_path. */
    public static function title(KnowledgeChunk $chunk): string
    {
        $path = str_getcsv(trim((string) $chunk->getAttribute('heading_path'), '{}'), ',', '"', '\\');
        $last = end($path);

        return is_string($last) ? $last : '';
    }
}
