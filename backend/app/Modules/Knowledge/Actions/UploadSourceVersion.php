<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Models\User;
use App\Modules\Knowledge\Enums\SourceType;
use App\Modules\Knowledge\Enums\VersionStatus;
use App\Modules\Knowledge\Jobs\IngestSourceVersion;
use App\Modules\Knowledge\Models\KnowledgeSource;
use App\Modules\Knowledge\Models\KnowledgeSourceVersion;
use App\Support\Api\ApiException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * «رفع إصدار جديد» (PROJECT1_PLAN.md S1.5): stores the PDF, creates the version as `uploaded` (for an existing or a
 * new source) and queues ingestion. The version stays out of retrieval until an admin reviews and publishes it.
 */
final class UploadSourceVersion
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** @param array{source_id?: string|null, new_source_title?: string|null, new_source_type?: string|null, edition: string, effective_from?: string|null, extraction_method: string} $data */
    public function __invoke(User $admin, UploadedFile $file, array $data): KnowledgeSourceVersion
    {
        $contents = (string) file_get_contents($file->getRealPath());
        if (! str_starts_with($contents, '%PDF-')) {
            throw new ApiException(415, 'unsupported_media_type', 'يُقبل ملف PDF فقط.');
        }
        $key = 'sources/'.Str::uuid7().'.pdf';
        Storage::disk((string) config('dentlegal.knowledge.disk'))->put($key, $contents);

        try {
            $version = DB::transaction(function () use ($admin, $file, $data, $contents, $key): KnowledgeSourceVersion {
                $source = isset($data['source_id'])
                    ? KnowledgeSource::query()->findOrFail($data['source_id'])
                    : KnowledgeSource::query()->create([
                        'code' => 'SRC-'.Str::upper(Str::random(6)),
                        'title_ar' => (string) $data['new_source_title'],
                        'type' => SourceType::from($data['new_source_type'] ?? SourceType::Regulation->value),
                    ]);
                $version = KnowledgeSourceVersion::query()->create([
                    'source_id' => $source->id,
                    'version_label' => mb_substr($data['edition'], 0, 60),
                    'edition' => $data['edition'],
                    'effective_from' => $data['effective_from'] ?? null,
                    'file_key' => $key,
                    'file_name' => mb_substr($file->getClientOriginalName() ?: 'source.pdf', 0, 200),
                    'sha256' => hash('sha256', $contents),
                    'requested_method' => $data['extraction_method'],
                    'status' => VersionStatus::Uploaded,
                    'uploaded_by' => $admin->id,
                ]);
                $this->audit->record('knowledge.version_uploaded', $admin, $version, ['source_id' => $source->id]);

                return $version;
            });
        } catch (UniqueConstraintViolationException) {
            Storage::disk((string) config('dentlegal.knowledge.disk'))->delete($key);
            throw new ApiException(409, 'version_exists', 'يوجد إصدار بالاسم نفسه لهذا المصدر.');
        }
        IngestSourceVersion::dispatch($version->id);

        return $version;
    }
}
