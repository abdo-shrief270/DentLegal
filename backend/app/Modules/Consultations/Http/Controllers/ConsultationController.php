<?php

declare(strict_types=1);

namespace App\Modules\Consultations\Http\Controllers;

use App\Modules\Consultations\Actions\AskQuestion;
use App\Modules\Consultations\Http\Requests\AskQuestionRequest;
use App\Modules\Consultations\Http\Resources\MessageResource;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Consultations\Models\ConsultationMessage;
use App\Modules\Identity\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** «المستشار القانوني الذكي» (PROJECT1_PLAN.md §4.2). Other dentists' threads and messages are 404. */
final class ConsultationController
{
    public function suggestedQuestions(): JsonResponse
    {
        return response()->json(['data' => array_values((array) config('dentlegal.consultant.suggested_questions'))]);
    }

    public function index(Request $request): JsonResponse
    {
        $page = Consultation::query()->where('user_id', $this->user($request)->id)
            ->orderByDesc('last_message_at')->orderByDesc('id')->cursorPaginate(20);

        return response()->json([
            'data' => collect($page->items())->map(fn (Consultation $c): array => [
                'id' => $c->id, 'title' => $c->title, 'last_message_at' => $c->last_message_at->toIso8601ZuluString(),
            ]),
            'meta' => ['next_cursor' => $page->nextCursor()?->encode()],
        ]);
    }

    /** Start a thread with its first question → 202 with the pending answer to poll. */
    public function store(AskQuestionRequest $request, AskQuestion $ask): JsonResponse
    {
        $answer = $ask($this->user($request), null, (string) $request->validated('question'), $request->idempotencyKey());

        return (new MessageResource($answer))->response()->setStatusCode(202);
    }

    public function show(Request $request, Consultation $consultation): JsonResponse
    {
        $this->own($request, $consultation);

        return response()->json(['data' => [
            'id' => $consultation->id,
            'title' => $consultation->title,
            'messages' => MessageResource::collection($consultation->messages()->get())->resolve($request),
        ]]);
    }

    /** Follow-up question → 202. 409 `answer_pending` while the previous answer is still being written. */
    public function ask(AskQuestionRequest $request, Consultation $consultation, AskQuestion $ask): JsonResponse
    {
        $this->own($request, $consultation);
        $answer = $ask($this->user($request), $consultation, (string) $request->validated('question'), $request->idempotencyKey());

        return (new MessageResource($answer))->response()->setStatusCode(202);
    }

    /** Polled every 2 s while `pending`. */
    public function message(Request $request, ConsultationMessage $message): MessageResource
    {
        $this->own($request, $message->consultation);

        return new MessageResource($message);
    }

    private function own(Request $request, Consultation $consultation): void
    {
        abort_unless($consultation->user_id === $this->user($request)->id, 404);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
