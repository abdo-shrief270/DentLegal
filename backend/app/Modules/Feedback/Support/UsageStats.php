<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Support;

use App\Modules\Identity\Enums\Role;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * «إحصائيات الاستخدام» (PROJECT1_PLAN.md §7.2, S6.3). Totals are aggregates over indexed columns of small pilot tables;
 * the daily series comes from the nightly roll-up (daily_usage_stats) plus today, computed live.
 */
final class UsageStats
{
    /** Metrics rolled up per day. */
    public const DAILY = ['questions', 'refusals', 'cases', 'drafts', 'approvals', 'ai_cost_usd'];

    /** @return array<string, mixed> */
    public function overview(string $range): array
    {
        $days = $range === '30d' ? 30 : 7;
        $from = now()->startOfDay()->subDays($days - 1);

        $answered = (int) DB::table('consultation_messages')->where('role', 'assistant')->where('status', 'answered')->where('answered_at', '>=', $from)->count();
        $refused = (int) DB::table('consultation_messages')->where('role', 'assistant')->where('status', 'refused')->where('answered_at', '>=', $from)->count();
        $useful = DB::table('ratings')->where('created_at', '>=', $from)->groupBy('target_type')
            ->selectRaw('target_type, avg(case when useful then 1.0 else 0.0 end) as rate, count(*) as n')->get()->keyBy('target_type');
        $overall = DB::table('ratings')->where('created_at', '>=', $from)->selectRaw('avg(case when useful then 1.0 else 0.0 end) as rate')->value('rate');
        $durations = DB::table('pipeline_jobs')->where('status', 'succeeded')->where('finished_at', '>=', $from)->selectRaw(
            'percentile_cont(0.5) within group (order by extract(epoch from (finished_at - created_at)) / 60) as p50,
             percentile_cont(0.9) within group (order by extract(epoch from (finished_at - created_at)) / 60) as p90',
        )->first();

        return [
            'range' => $range,
            'active_dentists' => (int) DB::table('personal_access_tokens')->where('tokenable_type', 'like', '%User')->where('last_used_at', '>=', $from)
                ->whereIn('tokenable_id', DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->where('roles.name', Role::Dentist->value)->select('model_id'))
                ->distinct()->count('tokenable_id'),
            'invited_dentists' => (int) DB::table('invitations')->where('role', Role::Dentist->value)->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '>', now())->count(),
            'questions_answered' => $answered,
            'refusal_rate' => $answered + $refused > 0 ? round($refused / ($answered + $refused), 3) : 0.0,
            'cases_created' => (int) DB::table('cases')->where('created_at', '>=', $from)->count(),
            'drafts_generated' => (int) DB::table('draft_versions')->where('author_type', 'ai')->where('created_at', '>=', $from)->count(),
            'drafts_approved' => (int) DB::table('drafts')->where('status', 'approved')->where('approved_at', '>=', $from)->count(),
            'useful_rate' => [
                'overall' => $overall === null ? null : round((float) $overall, 3),
                'consult' => self::rate($useful->get('consultation_message')),
                'analysis' => self::rate($useful->get('analysis')),
                'draft' => self::rate($useful->get('draft_version')),
            ],
            'upload_to_draft_minutes' => ['p50' => self::round($durations->p50 ?? null), 'p90' => self::round($durations->p90 ?? null)],
            'citation_validity' => self::round(DB::table('ai_runs')->whereNotNull('citation_validity')->where('created_at', '>=', $from)->avg('citation_validity'), 3),
            'ai_cost_usd' => round((float) DB::table('ai_runs')->where('created_at', '>=', $from)->sum('cost_usd'), 2),
            'daily' => $this->daily($from, $days),
        ];
    }

    /**
     * Writes one day's metrics (idempotent: re-running replaces the day).
     *
     * @return array<string, float>
     */
    public function rollUp(Carbon $day): array
    {
        $values = $this->measure($day->copy()->startOfDay(), $day->copy()->endOfDay());
        DB::table('daily_usage_stats')->upsert(
            array_map(fn (string $m, float $v): array => ['day' => $day->toDateString(), 'metric' => $m, 'value' => $v], array_keys($values), $values),
            ['day', 'metric'], ['value'],
        );

        return $values;
    }

    /** @return list<array{day: string, questions: int, cases: int}> */
    private function daily(Carbon $from, int $days): array
    {
        $rolled = DB::table('daily_usage_stats')->whereBetween('day', [$from->toDateString(), now()->subDay()->toDateString()])
            ->whereIn('metric', ['questions', 'cases'])->get()->groupBy('day');
        $today = $this->measure(now()->startOfDay(), now());
        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $rows = $rolled->get($day)?->keyBy('metric');
            $isToday = $day === now()->toDateString();
            $out[] = [
                'day' => $day,
                'questions' => (int) ($isToday ? $today['questions'] : ($rows?->get('questions')->value ?? 0)),
                'cases' => (int) ($isToday ? $today['cases'] : ($rows?->get('cases')->value ?? 0)),
            ];
        }

        return $out;
    }

    /** @return array<string, float> */
    private function measure(Carbon $start, Carbon $end): array
    {
        $messages = fn (string $status) => (float) DB::table('consultation_messages')->where('role', 'assistant')->where('status', $status)->whereBetween('answered_at', [$start, $end])->count();

        return [
            'questions' => $messages('answered'),
            'refusals' => $messages('refused'),
            'cases' => (float) DB::table('cases')->whereBetween('created_at', [$start, $end])->count(),
            'drafts' => (float) DB::table('draft_versions')->where('author_type', 'ai')->whereBetween('created_at', [$start, $end])->count(),
            'approvals' => (float) DB::table('drafts')->where('status', 'approved')->whereBetween('approved_at', [$start, $end])->count(),
            'ai_cost_usd' => (float) DB::table('ai_runs')->whereBetween('created_at', [$start, $end])->sum('cost_usd'),
        ];
    }

    private static function rate(?object $row): ?float
    {
        return $row === null ? null : round((float) $row->rate, 3);
    }

    private static function round(mixed $value, int $precision = 1): ?float
    {
        return $value === null ? null : round((float) $value, $precision);
    }
}
