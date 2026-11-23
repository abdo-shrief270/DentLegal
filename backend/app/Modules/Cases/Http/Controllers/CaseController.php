<?php

declare(strict_types=1);

namespace App\Modules\Cases\Http\Controllers;

use App\Modules\Cases\Actions\CreateCase;
use App\Modules\Cases\Enums\CaseStatus;
use App\Modules\Cases\Http\Requests\StoreCaseRequest;
use App\Modules\Cases\Http\Resources\CaseResource;
use App\Modules\Cases\Models\DentalCase;
use App\Modules\Identity\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class CaseController
{
    /** The dentist's cases, most recently updated first, cursor-paginated. */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', DentalCase::class);
        $request->validate([
            'status' => ['nullable', Rule::enum(CaseStatus::class)],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);
        /** @var User $user */
        $user = $request->user();

        $cases = DentalCase::query()
            ->where('dentist_id', $user->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->with(['currentAnalysis', 'draft.reviewer'])
            ->orderByDesc('updated_at')->orderByDesc('id')
            ->cursorPaginate($request->integer('per_page', 20));

        return CaseResource::collection($cases);
    }

    public function store(StoreCaseRequest $request, CreateCase $action): JsonResponse
    {
        Gate::authorize('create', DentalCase::class);
        /** @var User $user */
        $user = $request->user();

        return (new CaseResource($action($user, $request->validated())))->response()->setStatusCode(201);
    }

    public function show(DentalCase $case): CaseResource
    {
        Gate::authorize('view', $case);

        return new CaseResource($case->load(['currentAnalysis', 'draft.reviewer']));
    }
}
