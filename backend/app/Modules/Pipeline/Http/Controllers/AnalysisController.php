<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Http\Controllers;

use App\Modules\Cases\Models\DentalCase;
use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Actions\StartAnalysis;
use App\Modules\Pipeline\Models\Analysis;
use App\Modules\Pipeline\Models\PipelineJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class AnalysisController
{
    /** Start (or resume) the analysis pipeline. Send `Idempotency-Key` to make double taps safe. */
    public function start(Request $request, DentalCase $case, StartAnalysis $action): JsonResponse
    {
        Gate::authorize('update', $case);
        /** @var User $user */
        $user = $request->user();
        $key = $request->header('Idempotency-Key');
        $job = $action($user, $case, is_string($key) && $key !== '' ? mb_substr($key, 0, 100) : null);

        return response()->json(['data' => ['job_id' => $job->id, 'status' => $job->status]], 202);
    }

    /** Polled every 3 s by the «التحليل الذكي» screen. */
    public function status(DentalCase $case): JsonResponse
    {
        Gate::authorize('view', $case);
        /** @var PipelineJob $job */
        $job = PipelineJob::query()->where('case_id', $case->id)->latest('created_at')->firstOrFail();

        return response()->json(['data' => [
            'job_id' => $job->id,
            'status' => $job->status,
            'current_step' => $job->current_step,
            'progress' => $job->progress(),
            'steps' => collect($job->steps)->map(fn (array $s, string $key): array => ['key' => $key, ...$s])->values(),
            'started_at' => $job->started_at?->toIso8601ZuluString(),
            'finished_at' => $job->finished_at?->toIso8601ZuluString(),
            'error' => $job->error_code,
        ]]);
    }

    public function show(DentalCase $case): JsonResponse
    {
        Gate::authorize('view', $case);
        /** @var Analysis $analysis */
        $analysis = Analysis::query()->where('case_id', $case->id)->where('is_current', true)->firstOrFail();
        $result = $analysis->result_json;
        unset($result['raw']); // the model's own form, kept for the draft call only

        return response()->json(['data' => [
            'id' => $analysis->id,
            ...$result,
            'needs_attention' => $analysis->needs_attention,
            'created_at' => $analysis->created_at->toIso8601ZuluString(),
        ]]);
    }
}
