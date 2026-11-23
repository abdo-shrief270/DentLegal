<?php

declare(strict_types=1);

namespace App\Modules\Review\Support;

use App\Modules\Pipeline\Models\Analysis;
use App\Modules\Pipeline\Models\Draft;
use App\Modules\Pipeline\Models\DraftVersion;
use App\Modules\Review\Actions\ApproveDraft;
use App\Modules\Review\Models\ReviewNote;

/**
 * Review payloads for the dashboard (shapes in web/dashboard/app/types/api.ts, snake_case). Text stays masked:
 * lawyers see `[PATIENT]` and `{{DENTIST_NAME}}` as placeholders, as the dentist's own data is filled only on display
 * to the dentist.
 */
final class ReviewPresenter
{
    /** @return array<string, mixed> */
    public static function listItem(Draft $draft, ?Analysis $analysis): array
    {
        $case = $draft->case;
        $content = $draft->currentVersion !== null ? $draft->currentVersion->content_json : [];

        return [
            'draft_id' => $draft->id,
            'case_code' => $case->code,
            'title' => $case->title,
            'case_type' => $case->case_type->value,
            'dentist_name' => $case->dentist->name,
            'risk' => $analysis?->risk_level,
            'position_strength' => $analysis?->position_strength,
            'submitted_at' => $case->submitted_for_review_at?->toIso8601ZuluString(),
            'claimed_by' => $draft->reviewer_id,
            'claimed_by_name' => $draft->reviewer?->name,
            'claimed_at' => $draft->claimed_at?->toIso8601ZuluString(),
            'approved_at' => $draft->approved_at?->toIso8601ZuluString(),
            'status' => $draft->status,
            'flags_count' => ApproveDraft::warnings($content) + count($content['flags'] ?? []),
        ];
    }

    /** @return array<string, mixed> */
    public static function detail(Draft $draft, ?Analysis $analysis, ReviewContext $context): array
    {
        $case = $draft->case;
        $profile = $case->dentist->dentistProfile;
        $versions = $draft->versions()->with('author:id,name')->orderBy('version_no')->get();
        /** @var DraftVersion|null $current */
        $current = $versions->firstWhere('id', $draft->current_version_id);
        $documents = [];
        foreach ($context->documents as $ref => $doc) {
            $documents[] = [
                'ref' => $ref, 'document_id' => $doc['document_id'], 'name' => $doc['name'], 'kind' => $doc['kind'], 'pages' => count($doc['pages']),
                'low_quality' => (bool) ($doc['ocr_confidence'] !== null && $doc['ocr_confidence'] < 0.75),
                'masked_text' => implode("\n\n", array_column($doc['pages'], 'text')),
            ];
        }

        return [
            ...self::listItem($draft, $analysis),
            'claim_type' => $case->claim_type->value,
            'authority' => $case->authority,
            'complaint_no' => $case->complaint_no,
            'procedure' => $case->procedure,
            'procedure_date' => $case->procedure_date->toDateString(),
            'patient_code' => $case->patient_code,
            'facts_masked' => $case->facts_masked,
            'dentist' => ['name' => $case->dentist->name, 'specialty' => $profile?->specialty, 'city' => $profile?->city],
            'approved_by' => $draft->status === 'approved' ? $draft->reviewer?->name : null,
            'analysis' => $analysis === null ? null : [
                'summary' => $analysis->result_json['summary'] ?? null,
                'sources_sufficient' => $analysis->sources_sufficient,
            ],
            'versions' => $versions->map(fn (DraftVersion $v): array => [
                'id' => $v->id,
                'no' => $v->version_no,
                'author_type' => $v->author_type,
                'author_name' => $v->author_type === 'ai' ? 'DentLegal AI' : $v->author?->name,
                'created_at' => $v->created_at->toIso8601ZuluString(),
                'note' => $v->change_note,
                'locked' => $v->locked_at !== null,
                'content' => self::withIds($v->content_json),
            ])->values()->all(),
            'sources' => collect($context->sources)->map(fn (array $s, string $ref): array => ['ref' => $ref, ...$s])->values()->all(),
            'documents' => $documents,
            'checks' => self::checks($current->content_json ?? [], $analysis, $documents),
            'notes' => ReviewNote::query()->with('author:id,name')->where('draft_id', $draft->id)->orderBy('created_at')->get()
                ->map(fn (ReviewNote $n): array => ['id' => $n->id, 'author' => $n->author?->name, 'created_at' => $n->created_at->toIso8601ZuluString(), 'text' => $n->text])
                ->values()->all(),
        ];
    }

    /**
     * Stable paragraph ids for the editor: `intro` and `s{section}p{paragraph}`, unless the version already has them.
     *
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private static function withIds(array $content): array
    {
        if (isset($content['intro'])) {
            $content['intro']['id'] ??= 'intro';
        }
        foreach ($content['sections'] ?? [] as $s => $section) {
            foreach ($section['paragraphs'] ?? [] as $p => $paragraph) {
                $content['sections'][$s]['paragraphs'][$p]['id'] ??= "s{$s}p{$p}";
            }
        }
        unset($content['needs_attention']);

        return $content;
    }

    /**
     * «فحوصات الجودة» beside the draft.
     *
     * @param  array<string, mixed>  $content
     * @param  list<array<string, mixed>>  $documents
     * @return list<array{ok: bool, text: string}>
     */
    private static function checks(array $content, ?Analysis $analysis, array $documents): array
    {
        $paragraphs = [$content['intro'] ?? [], ...array_merge(...array_map(fn (array $s): array => $s['paragraphs'] ?? [], $content['sections'] ?? []))];
        $citations = array_merge(...array_map(fn (array $p): array => $p['citations'] ?? [], $paragraphs));
        $unverified = count(array_filter($citations, fn (array $c): bool => ! ($c['verified'] ?? false)));
        $unsupported = count(array_filter($paragraphs, fn (array $p): bool => (bool) ($p['unsupported'] ?? false)));
        $lowQuality = count(array_filter($documents, fn (array $d): bool => $d['low_quality']));
        $sufficient = $analysis !== null && $analysis->sources_sufficient;

        return [
            ['ok' => $unverified === 0, 'text' => $unverified === 0 ? 'جميع الاستشهادات موثقة في مصادرها' : "استشهادات غير موثقة: {$unverified}"],
            ['ok' => $unsupported === 0, 'text' => $unsupported === 0 ? 'كل دفع قانوني مسند إلى مصدر' : "دفوع قانونية غير مسندة: {$unsupported}"],
            ['ok' => $sufficient, 'text' => $sufficient ? 'المصادر المسترجعة كافية' : 'المصادر المسترجعة غير كافية؛ راجع الاستناد النظامي'],
            ['ok' => $lowQuality === 0, 'text' => $lowQuality === 0 ? 'جودة قراءة المستندات جيدة' : "مستندات بجودة قراءة منخفضة: {$lowQuality}"],
            ...array_map(fn (string $f): array => ['ok' => false, 'text' => $f], array_values(array_filter([...($content['unsupported_points'] ?? []), ...array_filter($content['flags'] ?? [], 'is_string')]))),
        ];
    }
}
