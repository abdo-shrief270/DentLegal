<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Cases\Models\DentalCase;
use App\Modules\Pipeline\Models\AiRun;
use App\Modules\Pipeline\Models\Analysis;
use App\Modules\Pipeline\Models\Draft;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * «سجل تشغيل الذكاء الاصطناعي» (S7.4): a read-only view of ai_runs with filters and a summary of the filtered set.
 * Rows carry metadata only (PROJECT1_PLAN.md §5.13): no prompt, question, answer or document text is stored or shown.
 */
final class AdminAiRunController
{
    private const LIMIT = 500;

    public function index(Request $request): JsonResponse
    {
        $f = $request->validate([
            'run_type' => ['nullable', 'string', 'max:24'],
            'status' => ['nullable', 'string', 'max:24'],
            'problems' => ['nullable', 'in:0,1,true,false'],
            'from' => ['nullable', 'date'], 'to' => ['nullable', 'date'],
        ]);
        $filtered = fn (): Builder => AiRun::query()
            ->when($f['run_type'] ?? null, fn (Builder $q, string $t) => $q->where('run_type', $t))
            ->when($f['status'] ?? null, fn (Builder $q, string $s) => $q->where('status', $s))
            ->when(filter_var($f['problems'] ?? false, FILTER_VALIDATE_BOOLEAN), fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereNotNull('error_code')->orWhereNotNull('refusal_category')->orWhere('fallback_used', true)
                ->orWhereIn('status', ['failed', 'refused', 'needs_attention'])))
            ->when($f['from'] ?? null, fn (Builder $q, string $d) => $q->where('created_at', '>=', $d))
            ->when($f['to'] ?? null, fn (Builder $q, string $d) => $q->where('created_at', '<', now()->parse($d)->addDay()));

        $runs = $filtered()->withCount('sources')->orderByDesc('created_at')->orderByDesc('id')->limit(self::LIMIT)->get();
        $codes = self::caseCodes($runs);

        $s = $filtered()->toBase()->selectRaw(
            'count(*) as runs, coalesce(sum(cost_usd), 0) as cost, coalesce(sum(input_tokens), 0) as input_tokens,
             coalesce(sum(output_tokens), 0) as output_tokens, coalesce(sum(cache_read_tokens), 0) as cache_read_tokens,
             percentile_cont(0.5) within group (order by latency_ms) as p50, percentile_cont(0.9) within group (order by latency_ms) as p90,
             avg(citation_validity) as citation_validity, count(*) filter (where fallback_used) as fallbacks,
             count(*) filter (where error_code is not null) as errors'
        )->first();

        return response()->json([
            'data' => $runs->map(fn (AiRun $r): array => [
                'id' => (string) $r->id,
                'created_at' => $r->created_at->toIso8601ZuluString(),
                'run_type' => $r->run_type,
                'status' => $r->status,
                'case_code' => $codes[$r->subject_type.':'.$r->subject_id] ?? null,
                'subject_type' => $r->subject_type,
                'prompt_version' => $r->prompt_version,
                'model_requested' => $r->model_requested,
                'model_served' => $r->model_served,
                'effort' => $r->effort,
                'inference_geo' => $r->inference_geo,
                'fallback_used' => $r->fallback_used,
                'stop_reason' => $r->stop_reason,
                'refusal_category' => $r->refusal_category,
                'error_code' => $r->error_code,
                'input_tokens' => $r->input_tokens,
                'output_tokens' => $r->output_tokens,
                'cache_read_tokens' => $r->cache_read_tokens,
                'cost_usd' => round((float) $r->cost_usd, 4),
                'latency_ms' => $r->latency_ms,
                'citation_validity' => $r->citation_validity,
                'repaired' => $r->repaired,
                'sources' => $r->sources_count,
                'request_id' => $r->anthropic_request_id,
            ])->values()->all(),
            'meta' => [
                'runs' => (int) ($s->runs ?? 0),
                'shown' => $runs->count(),
                'cost_usd' => round((float) ($s->cost ?? 0), 4),
                'input_tokens' => (int) ($s->input_tokens ?? 0),
                'output_tokens' => (int) ($s->output_tokens ?? 0),
                'cache_read_tokens' => (int) ($s->cache_read_tokens ?? 0),
                'latency_p50_ms' => $s->p50 === null ? null : (int) round((float) $s->p50),
                'latency_p90_ms' => $s->p90 === null ? null : (int) round((float) $s->p90),
                'citation_validity' => $s->citation_validity === null ? null : round((float) $s->citation_validity, 3),
                'fallbacks' => (int) ($s->fallbacks ?? 0),
                'errors' => (int) ($s->errors ?? 0),
                'run_types' => AiRun::query()->distinct()->orderBy('run_type')->pluck('run_type')->all(),
            ],
        ]);
    }

    /**
     * The case a run belongs to, for the analysis and draft runs (consultation runs have none).
     *
     * @param  Collection<int, AiRun>  $runs
     * @return array<string, string>
     */
    private static function caseCodes(Collection $runs): array
    {
        $ids = fn (string $type): array => $runs->where('subject_type', $type)->pluck('subject_id')->filter()->unique()->values()->all();
        $caseIds = [];
        foreach ([['DentalCase', null], ['Analysis', Analysis::class], ['Draft', Draft::class]] as [$type, $model]) {
            $rows = $model === null
                ? collect($ids($type))->mapWithKeys(fn ($id) => [$id => $id])
                : $model::query()->whereIn('id', $ids($type))->pluck('case_id', 'id');
            foreach ($rows as $subjectId => $caseId) {
                $caseIds["{$type}:{$subjectId}"] = (string) $caseId;
            }
        }
        $codes = DentalCase::withTrashed()->whereIn('id', array_unique(array_values($caseIds)))->pluck('code', 'id');

        return array_filter(array_map(fn (string $caseId) => $codes[$caseId] ?? null, $caseIds));
    }
}
