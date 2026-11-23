<?php

declare(strict_types=1);

namespace App\Modules\Consultations\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Consultations\Jobs\AnswerConsultation;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Consultations\Models\ConsultationMessage;
use App\Modules\Identity\Enums\ConsentType;
use App\Modules\Identity\Models\User;
use App\Support\Api\ApiException;
use App\Support\Masking\PatternMasker;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * POST /consultations and POST /consultations/{id}/messages (PROJECT1_PLAN.md §4.2, §5.7). The question is
 * pattern-masked before it is stored; ai-svc masks names (NER) in the job before the question reaches Claude.
 * Returns the pending answer, which the app polls. An Idempotency-Key makes double taps return the same answer row.
 */
final class AskQuestion
{
    public function __construct(private readonly PatternMasker $masker, private readonly AuditLogger $audit) {}

    public function __invoke(User $dentist, ?Consultation $consultation, string $question, ?string $idempotencyKey): ConsultationMessage
    {
        if ($idempotencyKey !== null && ($existing = $this->replay($dentist, $consultation, $idempotencyKey)) !== null) {
            return $existing;
        }
        if (! $dentist->hasConsent(ConsentType::AiProcessing)) {
            throw ApiException::forbidden('consent_required', 'يلزم الموافقة على المعالجة الآلية قبل استخدام المستشار القانوني.');
        }
        $hourKey = 'consult:h:'.$dentist->id;
        $dayKey = 'consult:d:'.$dentist->id;
        foreach ([$hourKey => (int) config('dentlegal.consultant.per_hour'), $dayKey => (int) config('dentlegal.consultant.per_day')] as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                throw ApiException::rateLimited(RateLimiter::availableIn($key));
            }
        }

        $masked = $this->masker->mask(trim($question));
        try {
            $answer = DB::transaction(function () use ($dentist, $consultation, $masked, $idempotencyKey): ConsultationMessage {
                if ($consultation === null) {
                    $consultation = Consultation::query()->create([
                        'user_id' => $dentist->id,
                        'title' => mb_substr($masked->text, 0, 80),
                        'idempotency_key' => $idempotencyKey,
                        'last_message_at' => now(),
                    ]);
                } else {
                    // Serialises questions in one thread; the partial unique index is the backstop.
                    $consultation = Consultation::query()->whereKey($consultation->id)->lockForUpdate()->firstOrFail();
                    if (ConsultationMessage::query()->where('consultation_id', $consultation->id)->where('status', 'pending')->exists()) {
                        throw new ApiException(409, 'answer_pending', 'انتظر إجابة السؤال السابق قبل إرسال سؤال جديد.');
                    }
                    $consultation->update(['last_message_at' => now()]);
                }
                $asked = ConsultationMessage::query()->create([
                    'consultation_id' => $consultation->id, 'role' => 'user', 'text_masked' => $masked->text, 'idempotency_key' => $idempotencyKey,
                ]);
                $answer = ConsultationMessage::query()->create([
                    'consultation_id' => $consultation->id, 'role' => 'assistant', 'reply_to_id' => $asked->id, 'status' => 'pending',
                ]);
                $this->audit->record('consultation.asked', $dentist, $consultation, ['message_id' => $asked->id, 'masked_entities' => $masked->entityCounts]);
                AnswerConsultation::dispatch($answer->id)->afterCommit();

                return $answer;
            });
        } catch (QueryException $e) {
            // A concurrent double tap with the same key lost the race on the unique index: return the winner's answer.
            if ($idempotencyKey !== null && ($existing = $this->replay($dentist, $consultation, $idempotencyKey)) !== null) {
                return $existing;
            }
            throw $e;
        }
        RateLimiter::hit($hourKey, 3600);
        RateLimiter::hit($dayKey, 86400);

        return $answer;
    }

    private function replay(User $dentist, ?Consultation $consultation, string $key): ?ConsultationMessage
    {
        $asked = $consultation === null
            ? ConsultationMessage::query()->where('role', 'user')
                ->whereHas('consultation', fn ($q) => $q->where('user_id', $dentist->id)->where('idempotency_key', $key))
                ->oldest()->first()
            : ConsultationMessage::query()->where('consultation_id', $consultation->id)->where('idempotency_key', $key)->first();

        return $asked === null ? null : ConsultationMessage::query()->where('reply_to_id', $asked->id)->first();
    }
}
