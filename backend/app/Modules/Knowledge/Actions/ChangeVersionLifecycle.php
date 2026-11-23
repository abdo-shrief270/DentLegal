<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Models\User;
use App\Modules\Knowledge\Enums\VersionStatus;
use App\Modules\Knowledge\Jobs\IngestSourceVersion;
use App\Modules\Knowledge\Models\KnowledgeSourceVersion;
use App\Support\Api\ApiException;
use Illuminate\Support\Facades\DB;

/** «أرشفة» and «إعادة الفهرسة» (publishing is PublishSourceVersion). */
final class ChangeVersionLifecycle
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** Takes a version out of retrieval. Archiving the active version leaves the source with none until a publish. */
    public function archive(User $admin, KnowledgeSourceVersion $version): KnowledgeSourceVersion
    {
        return DB::transaction(function () use ($admin, $version): KnowledgeSourceVersion {
            /** @var KnowledgeSourceVersion $locked */
            $locked = KnowledgeSourceVersion::query()->whereKey($version->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, [VersionStatus::Active, VersionStatus::Review], true)) {
                throw ApiException::invalidState('يمكن أرشفة الإصدار المنشور أو قيد المراجعة فقط.', ['status' => $locked->status->value]);
            }
            $locked->update(['status' => VersionStatus::Archived, 'archived_at' => now()]);
            $locked->chunks()->toBase()->update(['status' => VersionStatus::Archived->value]);
            $this->audit->record('knowledge.version_archived', $admin, $locked);

            return $locked;
        });
    }

    /** Runs ingestion again (new parser or a different method). Active and archived versions are fixed: upload anew. */
    public function reindex(User $admin, KnowledgeSourceVersion $version, ?string $method): KnowledgeSourceVersion
    {
        $updated = DB::transaction(function () use ($admin, $version, $method): KnowledgeSourceVersion {
            /** @var KnowledgeSourceVersion $locked */
            $locked = KnowledgeSourceVersion::query()->whereKey($version->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, [VersionStatus::Uploaded, VersionStatus::Review, VersionStatus::Failed], true)) {
                throw ApiException::invalidState('لا يمكن إعادة فهرسة إصدار منشور أو مؤرشف أو قيد المعالجة؛ ارفع إصدارًا جديدًا.', ['status' => $locked->status->value]);
            }
            $locked->update(['status' => VersionStatus::Uploaded, 'error' => null, 'requested_method' => $method ?? $locked->requested_method]);
            $this->audit->record('knowledge.version_reindexed', $admin, $locked);

            return $locked;
        });
        IngestSourceVersion::dispatch($updated->id);

        return $updated;
    }
}
