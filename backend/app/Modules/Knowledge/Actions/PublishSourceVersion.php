<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Models\User;
use App\Modules\Knowledge\Enums\VersionStatus;
use App\Modules\Knowledge\Models\KnowledgeSource;
use App\Modules\Knowledge\Models\KnowledgeSourceVersion;
use App\Support\Api\ApiException;
use Illuminate\Support\Facades\DB;

/**
 * «نشر الإصدار»: in one transaction, archive the source's current active version (and its chunks) and activate this one.
 * Retrieval sees the switch atomically: there is never a moment with zero or two active versions.
 */
final class PublishSourceVersion
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(KnowledgeSourceVersion $version, User $admin): KnowledgeSourceVersion
    {
        return DB::transaction(function () use ($version, $admin): KnowledgeSourceVersion {
            // Lock the source row so two publishes of the same source run one after the other.
            KnowledgeSource::query()->whereKey($version->source_id)->lockForUpdate()->firstOrFail();
            /** @var KnowledgeSourceVersion $version */
            $version = KnowledgeSourceVersion::query()->whereKey($version->getKey())->lockForUpdate()->firstOrFail();

            if ($version->status !== VersionStatus::Review) {
                throw ApiException::invalidState('يمكن نشر الإصدار بعد اكتمال المعالجة ومراجعته فقط.', ['status' => $version->status->value]);
            }
            if ($version->chunks()->whereNull('embedding')->exists() || $version->chunk_count === 0) {
                throw ApiException::invalidState('الإصدار لا يحتوي على مقاطع مفهرسة بالكامل.');
            }

            $previous = KnowledgeSourceVersion::query()
                ->where('source_id', $version->source_id)
                ->where('status', VersionStatus::Active)
                ->lockForUpdate()
                ->first();
            if ($previous !== null) {
                $previous->update(['status' => VersionStatus::Archived, 'archived_at' => now()]);
                $previous->chunks()->toBase()->update(['status' => VersionStatus::Archived->value]);
            }

            $version->update(['status' => VersionStatus::Active, 'published_by' => $admin->id, 'published_at' => now()]);
            $version->chunks()->toBase()->update(['status' => VersionStatus::Active->value]);

            $this->audit->record('knowledge.version_published', $admin, $version, [
                'source_id' => $version->source_id,
                'archived_version_id' => $previous?->id,
                'chunk_count' => $version->chunk_count,
            ]);

            return $version;
        });
    }
}
