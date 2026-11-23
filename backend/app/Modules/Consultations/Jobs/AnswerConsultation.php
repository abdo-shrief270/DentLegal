<?php

declare(strict_types=1);

namespace App\Modules\Consultations\Jobs;

use App\Modules\Ai\AiService;
use App\Modules\Ai\AiServiceException;
use App\Modules\Consultations\Models\ConsultationMessage;
use App\Modules\Pipeline\Support\AiRunRecorder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Answers one question (PROJECT1_PLAN.md §5.7): full masking with the thread's placeholder numbering → ai-svc
 * /v1/consult with the last answered turns → the answer row becomes answered or refused. The app polls the row;
 * answers usually take 10–25 s, so retries are short. Definitive failures end as `failed` with a code.
 */
final class AnswerConsultation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [5, 15];

    public int $timeout = 150;

    public function __construct(public string $messageId)
    {
        $this->onQueue('ai');
    }

    public function handle(AiService $ai, AiRunRecorder $recorder): void
    {
        $answer = ConsultationMessage::query()->with(['question', 'consultation.user.dentistProfile'])->findOrFail($this->messageId);
        $question = $answer->question;
        if ($answer->status !== 'pending' || $question === null) {
            return;
        }
        $consultation = $answer->consultation;
        $dentist = ['name' => $consultation->user->name, 'license_no' => $consultation->user->dentistProfile?->scfhs_license_no];

        try {
            $masked = $ai->mask([(string) $question->text_masked], [], $consultation->placeholder_state, $dentist);
            DB::transaction(function () use ($question, $consultation, $masked): void {
                $question->update(['text_masked' => $masked->texts[0]]);
                $consultation->update(['placeholder_state' => $masked->placeholderState]);
            });

            $response = $ai->consult($masked->texts[0], $this->history($answer));
        } catch (AiServiceException $e) {
            if ($e->retryable && $this->attempts() < $this->tries) {
                throw $e;
            }
            $this->markFailed($e->errorCode);

            return;
        }

        $refused = str_starts_with((string) ($response['status'] ?? ''), 'refused');
        DB::transaction(function () use ($answer, $response, $refused, $recorder): void {
            $run = $recorder->record($response['run'] ?? [], $response['sources'] ?? [], $refused ? 'refused' : 'succeeded', $answer);
            $answer->update([
                'status' => $refused ? 'refused' : 'answered',
                'answer_json' => [
                    ...($response['answer'] ?? []),
                    'refusal_reason' => $refused ? $response['status'] : null,
                    'needs_attention' => ($response['status'] ?? null) === 'needs_attention',
                    'sources' => $this->citedSources($response),
                ],
                'ai_run_id' => $run?->id,
                'answered_at' => now(),
            ]);
        });
    }

    public function failed(?Throwable $e): void
    {
        $this->markFailed($e instanceof AiServiceException ? $e->errorCode : 'internal_error');
    }

    private function markFailed(string $code): void
    {
        ConsultationMessage::query()->whereKey($this->messageId)->where('status', 'pending')
            ->update(['status' => 'failed', 'error_code' => $code, 'answered_at' => now()]);
    }

    /**
     * The last answered turns before this question, oldest first, as plain text (§5.7: history is context only).
     *
     * @return list<array{question: string, answer: string}>
     */
    private function history(ConsultationMessage $answer): array
    {
        $turns = ConsultationMessage::query()->with('question')
            ->where('consultation_id', $answer->consultation_id)->where('role', 'assistant')->where('status', 'answered')
            ->where('id', '<', $answer->id) // UUID v7: ordered by creation, finer than the timestamp's second
            ->orderByDesc('id')->limit((int) config('dentlegal.consultant.history_turns'))->get()->reverse();

        return $turns->map(fn (ConsultationMessage $m): array => [
            'question' => mb_substr((string) $m->question?->text_masked, 0, 2000),
            'answer' => mb_substr(implode("\n", array_column($m->answer_json['paragraphs'] ?? [], 'text')), 0, 4000),
        ])->values()->all();
    }

    /**
     * Only the sources the answer cites, for the «المصادر» chips; retrieval scores stay in ai_run_sources.
     *
     * @param  array<string, mixed>  $response
     * @return list<array<string, mixed>>
     */
    private function citedSources(array $response): array
    {
        $cited = [];
        foreach ($response['answer']['paragraphs'] ?? [] as $paragraph) {
            foreach ($paragraph['citations'] ?? [] as $citation) {
                if (($citation['verified'] ?? false) && isset($citation['chunk_id'])) {
                    $cited[(int) $citation['chunk_id']] = true;
                }
            }
        }

        return array_values(array_map(
            fn (array $s): array => ['ref' => $s['ref'] ?? null, 'chunk_id' => (int) $s['chunk_id'], 'label' => $s['label'] ?? null, 'law' => $s['law'] ?? null],
            array_filter($response['sources'] ?? [], fn (array $s): bool => isset($cited[(int) ($s['chunk_id'] ?? 0)])),
        ));
    }
}
