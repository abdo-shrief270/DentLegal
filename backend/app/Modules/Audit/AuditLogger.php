<?php

declare(strict_types=1);

namespace App\Modules\Audit;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Writes `audit_logs` rows (PROJECT1_PLAN.md §9.6). Metadata must never contain document text,
 * codes, tokens or keys: only ids, counts and states.
 */
final class AuditLogger
{
    public function __construct(private readonly Request $request) {}

    /** @param array<string, mixed> $metadata */
    public function record(string $action, ?User $actor = null, ?Model $subject = null, array $metadata = []): void
    {
        $actor ??= $this->request->user();

        AuditLog::query()->create([
            'occurred_at' => now(),
            'actor_id' => $actor?->getKey(),
            'actor_role' => $actor instanceof User ? $actor->primaryRole()?->value : null,
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject ? (string) $subject->getKey() : null,
            'ip' => $this->request->ip(),
            'user_agent' => mb_substr((string) $this->request->userAgent(), 0, 255) ?: null,
            'metadata' => $metadata,
        ]);
    }
}
