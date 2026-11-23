<?php

declare(strict_types=1);

namespace App\Modules\Ai;

/**
 * Port to ai-svc, the Python service that does OCR, PII masking, embeddings, hybrid retrieval, Claude calls
 * and citation verification (PROJECT1_PLAN.md §2.3 D1, §4.5). Laravel never calls Anthropic directly.
 */
interface AiService
{
    /**
     * Hybrid retrieval (dense + full text + trigram, fused with RRF, reranked) over active chunks.
     *
     * @param  array{source_codes?: list<string>, article_no?: int}  $filters
     * @return list<RetrievedChunk>
     */
    public function retrieve(string $query, int $topK = 8, array $filters = []): array;

    /**
     * Full PII masking (patterns + label heuristics + Arabic NER). Fails closed: throws instead of returning unmasked text.
     *
     * @param  list<string>  $texts
     * @param  list<string>  $allowlist  authority names and facility to keep
     * @param  array<string, mixed>|null  $placeholderState  from the previous call for the same case
     */
    public function mask(array $texts, array $allowlist = [], ?array $placeholderState = null): MaskedTexts;

    /** @return array{status: string, models: array<string, bool>, db: bool} */
    public function health(): array;
}
