<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Http\Controllers;

use App\Modules\Audit\AuditLogger;
use App\Modules\Cases\Actions\TransitionCase;
use App\Modules\Cases\Enums\CaseStatus;
use App\Modules\Cases\Models\DentalCase;
use App\Modules\Identity\Enums\ConsentType;
use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Models\Draft;
use App\Modules\Pipeline\Models\DraftVersion;
use App\Modules\Pipeline\Support\DraftRenderer;
use App\Support\Api\ApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class DraftController
{
    /** The current draft (AI or approved), with placeholders filled for display. */
    public function show(DentalCase $case): JsonResponse
    {
        Gate::authorize('view', $case);
        /** @var Draft $draft */
        $draft = Draft::query()->where('case_id', $case->id)->firstOrFail();
        // The dentist sees the AI draft until a lawyer approves; lawyer work in progress is not shown (§4.2).
        $version = $draft->status === 'approved'
            ? DraftVersion::query()->find($draft->approved_version_id)
            : DraftVersion::query()->where('draft_id', $draft->id)->where('author_type', 'ai')->orderByDesc('version_no')->first();
        abort_if($version === null, 404);

        return response()->json(['data' => [
            'draft_id' => $draft->id,
            'status' => $draft->status,
            'version' => [
                'id' => $version->id,
                'no' => $version->version_no,
                'author' => $version->author_type,
                'created_at' => $version->created_at->toIso8601ZuluString(),
                ...DraftRenderer::render($version->content_json, $case),
            ],
            'approval' => $draft->approved_at ? ['approved_at' => $draft->approved_at->toIso8601ZuluString(), 'approved_by' => $draft->reviewer?->name] : null,
        ]]);
    }

    /** «إرسال للمراجعة القانونية»: ai_ready → lawyer_review. Requires the lawyer-access consent. */
    public function submitReview(Request $request, DentalCase $case, TransitionCase $transition, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('update', $case);
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasConsent(ConsentType::LawyerAccess)) {
            throw ApiException::forbidden('consent_required', 'يلزم الموافقة على اطلاع المحامي على القضية قبل الإرسال.');
        }
        $updated = DB::transaction(function () use ($case, $transition, $audit, $user): DentalCase {
            $draft = Draft::query()->where('case_id', $case->id)->lockForUpdate()->first()
                ?? throw ApiException::invalidState('لا توجد مسودة لإرسالها.');
            $updated = $transition($case, CaseStatus::LawyerReview);
            $draft->update(['status' => 'in_review']);
            $audit->record('draft.submitted_for_review', $user, $draft, ['case_id' => $case->id]);

            return $updated;
        });

        return response()->json(['data' => ['status' => $updated->status->value, 'submitted_for_review_at' => $updated->submitted_for_review_at?->toIso8601ZuluString()]]);
    }
}
