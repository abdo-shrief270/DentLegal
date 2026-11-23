<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Http\Controllers;

use App\Modules\Audit\AuditLogger;
use App\Modules\Feedback\Models\Feedback;
use App\Modules\Feedback\Models\Rating;
use App\Modules\Feedback\Support\RatingTargets;
use App\Modules\Identity\Models\User;
use App\Support\Masking\PatternMasker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** POST /ratings (S6.1) and POST /feedback (S6.2) from the app. Free text is pattern-masked before it is stored. */
final class DentistFeedbackController
{
    public function __construct(private readonly PatternMasker $masker, private readonly AuditLogger $audit) {}

    public function rate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'target_type' => ['required', 'in:consultation_message,analysis,draft_version'],
            'target_id' => ['required', 'uuid'],
            'useful' => ['required', 'boolean'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);
        $user = $this->user($request);
        abort_unless(RatingTargets::ownedBy($user, $data['target_type'], $data['target_id']), 404);

        $rating = Rating::query()->updateOrCreate(
            ['user_id' => $user->id, 'target_type' => $data['target_type'], 'target_id' => $data['target_id']],
            ['useful' => (bool) $data['useful'], 'comment' => isset($data['comment']) && trim($data['comment']) !== '' ? $this->masker->mask(trim($data['comment']))->text : null],
        );

        return response()->json(['data' => ['id' => $rating->id, 'useful' => $rating->useful]], $rating->wasRecentlyCreated ? 201 : 200);
    }

    public function feedback(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', 'in:bug,suggestion,other'],
            'message' => ['required', 'string', 'min:3', 'max:2000'],
            'screen' => ['nullable', 'string', 'max:60'],
            'platform' => ['nullable', 'in:ios,android'],
        ]);
        $feedback = Feedback::query()->create([
            'user_id' => $this->user($request)->id,
            'category' => $data['category'],
            'message' => $this->masker->mask(trim($data['message']))->text,
            'screen' => $data['screen'] ?? null,
            'app_version' => mb_substr((string) $request->header('X-App-Version'), 0, 40) ?: null,
            'platform' => $data['platform'] ?? null,
        ]);
        $this->audit->record('feedback.sent', $this->user($request), $feedback, ['category' => $feedback->category]);

        return response()->json(['data' => ['id' => $feedback->id]], 201);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
