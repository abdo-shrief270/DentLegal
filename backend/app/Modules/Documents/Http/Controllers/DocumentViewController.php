<?php

declare(strict_types=1);

namespace App\Modules\Documents\Http\Controllers;

use App\Modules\Audit\AuditLogger;
use App\Modules\Documents\DocumentVault;
use App\Modules\Documents\Models\Document;
use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Models\Draft;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * «عرض المستند الأصلي» (PROJECT1_PLAN.md §4.2): a 5-minute signed link to the decrypted original. The dentist may open
 * their own case's files; a lawyer only the files of a case they have claimed. Issuing and opening are both audited.
 */
final class DocumentViewController
{
    private const TTL_MINUTES = 5;

    public function __construct(private readonly AuditLogger $audit) {}

    public function forDentist(Request $request, Document $document): JsonResponse
    {
        Gate::authorize('view', $document->case);

        return $this->issue($this->user($request), $document);
    }

    public function forLawyer(Request $request, Document $document): JsonResponse
    {
        $lawyer = $this->user($request);
        $claimed = Draft::query()->where('case_id', $document->case_id)->where('status', 'in_review')->where('reviewer_id', $lawyer->id)->exists();
        abort_unless($claimed, 404);

        return $this->issue($lawyer, $document);
    }

    /** The signed link itself: no session or token (it opens in a browser tab); the signature and expiry are the check. */
    public function file(Request $request, Document $document, DocumentVault $vault): Response
    {
        $viewer = User::query()->find($request->query('u'));
        $this->audit->record('document.viewed', $viewer, $document, ['case_id' => $document->case_id]);
        $contents = $vault->read($document->id, $document->storage_key, $document->wrapped_key);

        return response($contents, 200, [
            'Content-Type' => $document->mime,
            'Content-Disposition' => 'inline; filename="document.'.(['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'][$document->mime] ?? 'bin').'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    private function issue(User $viewer, Document $document): JsonResponse
    {
        $expires = now()->addMinutes(self::TTL_MINUTES);
        $url = URL::temporarySignedRoute('documents.file', $expires, ['document' => $document->id, 'u' => $viewer->id]);
        $this->audit->record('document.view_url_issued', $viewer, $document, ['case_id' => $document->case_id]);

        return response()->json(['data' => ['url' => $url, 'expires_at' => $expires->toIso8601ZuluString()]]);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
