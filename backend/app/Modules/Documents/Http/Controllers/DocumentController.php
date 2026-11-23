<?php

declare(strict_types=1);

namespace App\Modules\Documents\Http\Controllers;

use App\Modules\Cases\Models\DentalCase;
use App\Modules\Documents\Actions\DeleteDocument;
use App\Modules\Documents\Actions\UploadDocument;
use App\Modules\Documents\Enums\DocumentKind;
use App\Modules\Documents\Http\Resources\DocumentResource;
use App\Modules\Documents\Models\Document;
use App\Modules\Identity\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class DocumentController
{
    /** Upload one PDF or image (≤ 20 MB). Allowed only while the case is a draft. */
    public function store(Request $request, DentalCase $case, UploadDocument $action): JsonResponse
    {
        Gate::authorize('update', $case);
        $data = $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'kind' => ['required', Rule::enum(DocumentKind::class)],
        ]);
        /** @var User $user */
        $user = $request->user();
        $document = $action($user, $case, $data['file'], DocumentKind::from($data['kind']));

        return (new DocumentResource($document))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, DentalCase $case, Document $document, DeleteDocument $action): Response
    {
        Gate::authorize('update', $case);
        abort_unless($document->case_id === $case->id, 404);
        /** @var User $user */
        $user = $request->user();
        $action($user, $document);

        return response()->noContent();
    }
}
