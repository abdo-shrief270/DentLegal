<?php

declare(strict_types=1);

namespace App\Modules\Citations\Http\Controllers;

use App\Modules\Audit\AuditLogger;
use App\Modules\Citations\Models\Citation;
use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Support\RunSources;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * «مراجعة عينة الاستشهادات» (PROJECT1_PLAN.md §5.14, S4.6): a random sample of verified regulation citations from the
 * pilot, stratified by route, that a lawyer judges «صحيح / غير صحيح». The share judged correct is the pilot's
 * citation-accuracy KPI (target ≥ 95% on n ≥ 100). Claims are masked text; no case file is opened.
 */
final class CitationSampleController
{
    private const BATCH = 30;

    private const ROUTES = ['consult', 'analysis', 'draft'];

    public function index(): JsonResponse
    {
        // Draw a new batch when fewer than 10 sampled citations are still waiting for a verdict.
        if (Citation::query()->whereNotNull('sampled_at')->whereNull('lawyer_verdict')->count() < 10) {
            $this->draw();
        }
        $rows = Citation::query()->with('chunk.version.source')->whereNotNull('sampled_at')
            ->orderByRaw('lawyer_verdict IS NOT NULL')->orderByDesc('sampled_at')->orderBy('id')->limit(100)->get();
        $codes = DB::table('cases')->whereIn('id', $rows->pluck('case_id')->filter())->pluck('code', 'id');

        return response()->json([
            'data' => $rows->map(fn (Citation $c): array => $this->row($c, $codes[$c->case_id] ?? null))->values(),
            'meta' => $this->accuracy(),
        ]);
    }

    public function verdict(Request $request, Citation $citation, AuditLogger $audit): JsonResponse
    {
        abort_if($citation->sampled_at === null, 404);
        $data = $request->validate(['verdict' => ['required', 'in:correct,incorrect'], 'note' => ['nullable', 'string', 'max:500']]);
        /** @var User $lawyer */
        $lawyer = $request->user();
        $citation->forceFill([
            'lawyer_verdict' => $data['verdict'], 'lawyer_note' => $data['note'] ?? null, 'reviewed_by' => $lawyer->id, 'reviewed_at' => now(),
        ])->save();
        $audit->record('citation.judged', $lawyer, $citation, ['verdict' => $data['verdict'], 'route' => $citation->route]);
        $code = $citation->case_id ? DB::table('cases')->where('id', $citation->case_id)->value('code') : null;

        return response()->json(['data' => $this->row($citation->load('chunk.version.source'), $code), 'meta' => $this->accuracy()]);
    }

    /** Stratified random draw: an equal share per route, from verified regulation citations nobody has judged. */
    private function draw(): void
    {
        $per = intdiv(self::BATCH, count(self::ROUTES));
        foreach (self::ROUTES as $route) {
            $ids = Citation::query()->where('route', $route)->where('source_kind', 'knowledge')->where('verified', true)
                ->whereNotNull('chunk_id')->whereNull('sampled_at')->inRandomOrder()->limit($per)->pluck('id');
            Citation::query()->whereIn('id', $ids)->update(['sampled_at' => now()]);
        }
    }

    /** @return array{reviewed: int, correct: int, accuracy: float|null, by_route: array<string, float|null>} */
    private function accuracy(): array
    {
        $rows = DB::table('citations')->whereNotNull('lawyer_verdict')->groupBy('route')
            ->selectRaw("route, count(*) as n, count(*) filter (where lawyer_verdict = 'correct') as ok")->get()->keyBy('route');
        $n = (int) $rows->sum('n');
        $ok = (int) $rows->sum('ok');

        return [
            'reviewed' => $n,
            'correct' => $ok,
            'accuracy' => $n ? round($ok / $n, 4) : null,
            'by_route' => collect(self::ROUTES)->mapWithKeys(fn (string $r): array => [
                $r => isset($rows[$r]) && $rows[$r]->n > 0 ? round($rows[$r]->ok / $rows[$r]->n, 4) : null,
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function row(Citation $c, ?string $caseCode): array
    {
        $chunk = $c->chunk;

        return [
            'id' => (string) $c->id,
            'case_code' => $caseCode ?? '—',
            'route' => $c->route,
            'claim' => $c->claim,
            'quote' => $c->quote,
            'source_label' => $chunk === null ? $c->ref : trim(($chunk->version->source->title_ar ?? '').' · '.$chunk->label().' '.RunSources::title($chunk)),
            'source_text' => $chunk->display_text ?? '',
            'verdict' => $c->lawyer_verdict,
            'note' => $c->lawyer_note ?? '',
        ];
    }
}
