<?php

declare(strict_types=1);

namespace App\Modules\Review\Http\Controllers;

use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Models\Analysis;
use App\Modules\Pipeline\Models\Draft;
use App\Modules\Review\Actions\AddReviewNote;
use App\Modules\Review\Actions\ApproveDraft;
use App\Modules\Review\Actions\ClaimReview;
use App\Modules\Review\Actions\SaveReviewVersion;
use App\Modules\Review\Http\Requests\SaveVersionRequest;
use App\Modules\Review\Support\ReviewContext;
use App\Modules\Review\Support\ReviewPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** «قائمة المراجعة» and «مساحة المراجعة» for lawyers (PROJECT1_PLAN.md §4.2). Drafts not yet submitted are 404. */
final class ReviewController
{
    private const TABS = ['unclaimed', 'mine', 'approved'];

    public function index(Request $request): JsonResponse
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? (string) $request->query('tab') : 'unclaimed';
        $drafts = $this->tab($tab, $this->user($request))->with(['case.dentist', 'reviewer:id,name', 'currentVersion'])
            ->orderBy($tab === 'approved' ? 'approved_at' : 'updated_at', $tab === 'approved' ? 'desc' : 'asc')->limit(200)->get();
        $analyses = $this->analyses($drafts->pluck('case_id')->all());

        return response()->json(['data' => $drafts->map(fn (Draft $d): array => ReviewPresenter::listItem($d, $analyses[$d->case_id] ?? null))->values()]);
    }

    public function counts(Request $request): JsonResponse
    {
        $user = $this->user($request);

        return response()->json(['data' => collect(self::TABS)->mapWithKeys(fn (string $t): array => [$t => $this->tab($t, $user)->count()])]);
    }

    public function show(Draft $draft): JsonResponse
    {
        $draft = $this->reviewable($draft);

        return response()->json(['data' => $this->detail($draft)]);
    }

    public function claim(Request $request, Draft $draft, ClaimReview $claim): JsonResponse
    {
        $claim($this->user($request), $this->reviewable($draft));

        return response()->json(['data' => $this->detail($draft->refresh())]);
    }

    public function saveVersion(SaveVersionRequest $request, Draft $draft, SaveReviewVersion $save): JsonResponse
    {
        /** @var array<string, mixed> $content */
        $content = $request->validated('content');
        $save($this->user($request), $this->reviewable($draft), $content, $request->validated('note'));

        return response()->json(['data' => $this->detail($draft->refresh())], 201);
    }

    public function addNote(Request $request, Draft $draft, AddReviewNote $add): JsonResponse
    {
        $data = $request->validate(['text' => ['required', 'string', 'min:2', 'max:2000']]);
        $note = $add($this->user($request), $this->reviewable($draft), $data['text']);

        return response()->json(['data' => ['id' => $note->id, 'author' => $this->user($request)->name, 'created_at' => $note->created_at->toIso8601ZuluString(), 'text' => $note->text]], 201);
    }

    public function approve(Request $request, Draft $draft, ApproveDraft $approve): JsonResponse
    {
        $data = $request->validate(['confirm_warnings' => ['sometimes', 'boolean']]);
        $approve($this->user($request), $this->reviewable($draft), (bool) ($data['confirm_warnings'] ?? false));

        return response()->json(['data' => $this->detail($draft->refresh())]);
    }

    /** @return Builder<Draft> */
    private function tab(string $tab, User $user): Builder
    {
        return match ($tab) {
            'mine' => Draft::query()->where('status', 'in_review')->where('reviewer_id', $user->id),
            'approved' => Draft::query()->where('status', 'approved'),
            default => Draft::query()->where('status', 'in_review')->whereNull('reviewer_id'),
        };
    }

    private function reviewable(Draft $draft): Draft
    {
        abort_unless(in_array($draft->status, ['in_review', 'approved'], true), 404);

        return $draft;
    }

    /** @return array<string, mixed> */
    private function detail(Draft $draft): array
    {
        $draft->load(['case.dentist.dentistProfile', 'reviewer:id,name', 'currentVersion']);

        return ReviewPresenter::detail($draft, $this->analyses([$draft->case_id])[$draft->case_id] ?? null, ReviewContext::for($draft, $draft->case));
    }

    /**
     * @param  list<string>  $caseIds
     * @return array<string, Analysis>
     */
    private function analyses(array $caseIds): array
    {
        return Analysis::query()->whereIn('case_id', $caseIds)->where('is_current', true)->get()->keyBy('case_id')->all();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
