<?php

declare(strict_types=1);

namespace App\Modules\Citations\Support;

use App\Modules\Citations\Models\Citation;

/**
 * Writes one `citations` row per citation found in an AI output (consultant answer, analysis, AI draft version), with
 * the verifier's result. Idempotent per citable item: recording again replaces nothing that is already there.
 */
final class CitationRecorder
{
    /** @param array<string, mixed> $answer answer_json of a consultation message */
    public static function consult(string $messageId, array $answer): void
    {
        $items = [];
        foreach ($answer['paragraphs'] ?? [] as $i => $p) {
            $items["paragraphs[{$i}]"] = $p;
        }
        self::write('consultation_message', $messageId, 'consult', null, $items);
    }

    /** @param array<string, mixed> $result result_json of an analysis */
    public static function analysis(string $analysisId, string $caseId, array $result): void
    {
        $items = [];
        foreach (['strengths', 'weaknesses', 'recommended_actions'] as $key) {
            foreach ($result[$key] ?? [] as $i => $point) {
                $items["{$key}[{$i}]"] = $point;
            }
        }
        self::write('analysis', $analysisId, 'analysis', $caseId, $items);
    }

    /** @param array<string, mixed> $content content_json of a draft version */
    public static function draft(string $versionId, string $caseId, array $content): void
    {
        $items = isset($content['intro']) ? ['intro' => $content['intro']] : [];
        foreach ($content['sections'] ?? [] as $s => $section) {
            foreach ($section['paragraphs'] ?? [] as $p => $paragraph) {
                $items["sections[{$s}].paragraphs[{$p}]"] = $paragraph;
            }
        }
        self::write('draft_version', $versionId, 'draft', $caseId, $items);
    }

    /** @param array<string, mixed> $items path → {text, citations[]} */
    private static function write(string $type, string $id, string $route, ?string $caseId, array $items): void
    {
        foreach ($items as $path => $item) {
            foreach ($item['citations'] ?? [] as $c) {
                if (! isset($c['ref'], $c['quote'])) {
                    continue;
                }
                Citation::query()->firstOrCreate(
                    ['citable_type' => $type, 'citable_id' => $id, 'item_path' => $path, 'ref' => (string) $c['ref']],
                    [
                        'route' => $route, 'case_id' => $caseId,
                        'source_kind' => ($c['kind'] ?? '') === 'case_document' ? 'case_document' : 'knowledge',
                        'chunk_id' => $c['chunk_id'] ?? null, 'document_page_id' => $c['document_page_id'] ?? null,
                        'claim' => (string) ($item['text'] ?? ''), 'quote' => (string) $c['quote'],
                        'verified' => (bool) ($c['verified'] ?? false), 'verification' => $c['verification'] ?? null,
                    ],
                );
            }
        }
    }
}
