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
     * @param  array{name: string|null, license_no: string|null}|null  $dentist  replaced by {{DENTIST_NAME}} / {{DENTIST_LICENSE}}
     */
    public function mask(array $texts, array $allowlist = [], ?array $placeholderState = null, ?array $dentist = null): MaskedTexts;

    /**
     * Consultant answer for a masked question (+ the last turns), with verified citations and run metadata.
     *
     * @param  list<array{question: string, answer: string}>  $history
     * @return array<string, mixed> {status, answer, sources, run}
     */
    public function consult(string $maskedQuestion, array $history = []): array;

    /**
     * Case analysis from a masked bundle. Long-running: call from a queued job.
     *
     * @param  array<string, mixed>  $case  masked case fields (facts already masked)
     * @param  list<array<string, mixed>>  $documents  masked documents with pages
     * @return array<string, mixed> {status, analysis, sources, source_chunk_ids, run}
     */
    public function analyzeCase(array $case, array $documents): array;

    /**
     * Formal draft from the same bundle plus the analysis (its `raw` form) and the analysis's source chunk ids.
     *
     * @param  array<string, mixed>  $case
     * @param  list<array<string, mixed>>  $documents
     * @param  array<string, mixed>  $analysis
     * @param  list<int>  $sourceChunkIds
     * @return array<string, mixed> {status, draft, sources, run}
     */
    public function draftCase(array $case, array $documents, array $analysis, array $sourceChunkIds): array;

    /** @return array{status: string, models: array<string, bool>, db: bool} */
    public function health(): array;
}
