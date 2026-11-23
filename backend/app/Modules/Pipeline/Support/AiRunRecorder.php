<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Support;

use App\Modules\Pipeline\Models\AiRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Writes ai_runs (one row per Claude call) and ai_run_sources from an ai-svc response's `run` and `sources`. */
final class AiRunRecorder
{
    /**
     * @param  array<string, mixed>  $run
     * @param  list<array<string, mixed>>  $sources
     * @return AiRun|null the last call's row (the one whose output was kept), or null when no LLM call was made
     */
    public function record(array $run, array $sources, string $status, ?Model $subject = null): ?AiRun
    {
        $calls = $run['llm'] ?? [];
        if ($calls === []) {
            return null;
        }

        return DB::transaction(function () use ($run, $sources, $status, $subject, $calls): AiRun {
            $last = null;
            foreach ($calls as $i => $call) {
                $last = AiRun::query()->create([
                    'run_type' => (string) ($run['run_type'] ?? 'unknown'),
                    'subject_type' => $subject ? class_basename($subject) : null,
                    'subject_id' => $subject?->getKey(),
                    'status' => $i === array_key_last($calls) ? $status : 'superseded',
                    'prompt_version' => $run['prompt_version'] ?? null,
                    'schema_version' => $run['schema_version'] ?? null,
                    'model_requested' => $call['model_requested'] ?? null,
                    'model_served' => $call['model_served'] ?? null,
                    'effort' => $call['effort'] ?? null,
                    'inference_geo' => $call['inference_geo'] ?? null,
                    'fallback_used' => (bool) ($call['fallback_used'] ?? false),
                    'stop_reason' => $call['stop_reason'] ?? null,
                    'refusal_category' => $call['refusal_category'] ?? null,
                    'input_tokens' => (int) ($call['input_tokens'] ?? 0),
                    'output_tokens' => (int) ($call['output_tokens'] ?? 0),
                    'cache_read_tokens' => (int) ($call['cache_read_tokens'] ?? 0),
                    'cache_write_tokens' => (int) ($call['cache_write_tokens'] ?? 0),
                    'cost_usd' => (float) ($call['cost_usd'] ?? 0),
                    'latency_ms' => $call['latency_ms'] ?? null,
                    'citation_validity' => $i === array_key_last($calls) ? ($run['citation_validity'] ?? null) : null,
                    'repaired' => (bool) ($run['repaired'] ?? false),
                    'retrieval_params' => $run['retrieval'] ?? null,
                    'anthropic_request_id' => $call['anthropic_request_id'] ?? null,
                ]);
            }
            foreach ($sources as $source) {
                DB::table('ai_run_sources')->insert([
                    'ai_run_id' => $last->id,
                    'chunk_id' => (int) $source['chunk_id'],
                    'local_ref' => $source['ref'] ?? null,
                    'dense_rank' => $source['dense_rank'] ?? null,
                    'sparse_rank' => $source['sparse_rank'] ?? null,
                    'rerank_score' => $source['rerank_score'] ?? null,
                    'used_in_prompt' => (bool) ($source['used_in_prompt'] ?? true),
                ]);
            }

            return $last;
        });
    }
}
