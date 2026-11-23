<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Support;

use App\Modules\Consultations\Models\ConsultationMessage;
use App\Modules\Feedback\Models\Rating;
use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Models\Analysis;
use App\Modules\Pipeline\Models\DraftVersion;
use Illuminate\Support\Collection;

/** What can be rated, by whom, and how a rated item is described in the admin list (S6.1, S7.3). */
final class RatingTargets
{
    /** Only items the dentist was shown: their own answers, analyses, and AI or approved draft versions. */
    public static function ownedBy(User $dentist, string $type, string $id): bool
    {
        return match ($type) {
            'consultation_message' => ConsultationMessage::query()->whereKey($id)->where('role', 'assistant')->whereIn('status', ['answered', 'refused'])
                ->whereHas('consultation', fn ($q) => $q->where('user_id', $dentist->id))->exists(),
            'analysis' => Analysis::query()->whereKey($id)->whereIn('case_id', $dentist->cases()->select('id'))->exists(),
            'draft_version' => DraftVersion::query()->whereKey($id)
                ->whereHas('draft', fn ($q) => $q->whereIn('case_id', $dentist->cases()->select('id')))
                ->where(fn ($q) => $q->where('author_type', 'ai')->orWhereNotNull('locked_at'))->exists(),
            default => false,
        };
    }

    /**
     * Label and a short excerpt per rating, loaded in three queries.
     *
     * @param  Collection<int, Rating>  $ratings
     * @return array<string, array{label: string, excerpt: string}>
     */
    public static function describe(Collection $ratings): array
    {
        $ids = fn (string $type): array => $ratings->where('target_type', $type)->pluck('target_id')->all();
        $answers = ConsultationMessage::query()->with('question')->whereIn('id', $ids('consultation_message'))->get()->keyBy('id');
        $analyses = Analysis::query()->with('case:id,code')->whereIn('id', $ids('analysis'))->get()->keyBy('id');
        $versions = DraftVersion::query()->with('draft.case:id,code')->whereIn('id', $ids('draft_version'))->get()->keyBy('id');

        $out = [];
        foreach ($ratings as $r) {
            $out[$r->id] = match ($r->target_type) {
                'consultation_message' => ['label' => 'إجابة المستشار', 'excerpt' => self::cut((string) $answers->get($r->target_id)?->question?->text_masked)],
                'analysis' => ['label' => 'تحليل '.($analyses->get($r->target_id)?->case->code ?? ''), 'excerpt' => self::cut((string) ($analyses->get($r->target_id)?->result_json['summary'] ?? ''))],
                default => ['label' => 'مسودة '.($versions->get($r->target_id)?->draft->case->code ?? '').' · الإصدار '.($versions->get($r->target_id)->version_no ?? ''),
                    'excerpt' => self::cut((string) ($versions->get($r->target_id)?->content_json['title'] ?? ''))],
            };
        }

        return $out;
    }

    private static function cut(string $text): string
    {
        return mb_strlen($text) > 160 ? mb_substr($text, 0, 157).'…' : $text;
    }
}
