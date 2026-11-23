<?php

declare(strict_types=1);

namespace App\Modules\Review\Support;

/**
 * Checks the citations of a lawyer-edited version (PROJECT1_PLAN.md §5.9, simplified for human edits): the ref must
 * be one of the review's sources or documents and the quote must appear in it after Arabic normalisation. A legal
 * claim without a valid citation is flagged «غير مسند». Nothing is rejected: the lawyer sees the result.
 */
final class CitationChecker
{
    /**
     * @param  array<string, mixed>  $content  DraftContent as submitted (intro + sections → paragraphs → citations)
     * @return array<string, mixed> the same content with ids, verification and flags filled in
     */
    public static function check(array $content, ReviewContext $context): array
    {
        $content['intro'] = self::paragraph($content['intro'], 'intro', $context);
        foreach ($content['sections'] as $s => $section) {
            foreach ($section['paragraphs'] as $p => $paragraph) {
                $content['sections'][$s]['paragraphs'][$p] = self::paragraph($paragraph, "s{$s}p{$p}", $context);
            }
        }
        $content['flags'] = [];
        $content['unsupported_points'] = [];

        return $content;
    }

    /**
     * @param  array<string, mixed>  $paragraph
     * @return array<string, mixed>
     */
    private static function paragraph(array $paragraph, string $id, ReviewContext $context): array
    {
        $citations = [];
        foreach ($paragraph['citations'] ?? [] as $citation) {
            $citations[] = self::citation((string) $citation['ref'], (string) $citation['quote'], $context);
        }
        $legal = (bool) ($paragraph['legal_claim'] ?? false);
        $valid = array_filter($citations, fn (array $c): bool => $c['verified'] && $c['kind'] === 'knowledge');

        return [
            'id' => $paragraph['id'] ?? $id,
            'text' => (string) $paragraph['text'],
            'legal_claim' => $legal,
            'unsupported' => $legal && $valid === [],
            'article_mismatch' => false,
            'citations' => $citations,
        ];
    }

    /** @return array<string, mixed> */
    private static function citation(string $ref, string $quote, ReviewContext $context): array
    {
        $needle = self::normalize($quote);
        if (isset($context->sources[$ref])) {
            $source = $context->sources[$ref];
            $found = $needle !== '' && str_contains(self::normalize($source['text']), $needle);

            return ['ref' => $ref, 'kind' => 'knowledge', 'chunk_id' => $source['chunk_id'], 'label' => $source['label'], 'quote' => $quote,
                'verified' => $found, 'verification' => ['match' => $found ? 'exact' : 'none', 'reason' => $found ? null : 'quote_mismatch']];
        }
        if (isset($context->documents[$ref])) {
            foreach ($context->documents[$ref]['pages'] as $page) {
                if ($needle !== '' && str_contains(self::normalize($page['text']), $needle)) {
                    return ['ref' => $ref, 'kind' => 'case_document', 'document_id' => $context->documents[$ref]['document_id'],
                        'document_page_id' => $page['document_page_id'], 'page' => $page['page_no'], 'quote' => $quote,
                        'verified' => true, 'verification' => ['match' => 'exact', 'reason' => null]];
                }
            }

            return ['ref' => $ref, 'kind' => 'case_document', 'document_id' => $context->documents[$ref]['document_id'], 'quote' => $quote,
                'verified' => false, 'verification' => ['match' => 'none', 'reason' => 'quote_mismatch']];
        }

        return ['ref' => $ref, 'kind' => 'unknown', 'quote' => $quote, 'verified' => false, 'verification' => ['match' => 'none', 'reason' => 'ref_not_in_context']];
    }

    /** Same idea as ai-svc's search form: no diacritics or tatweel, one alef/yaa/taa-marbuta form, single spaces. */
    public static function normalize(string $text): string
    {
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $text) ?? $text;
        $text = strtr($text, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ى' => 'ي', 'ة' => 'ه', '«' => '', '»' => '', '"' => '']);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
