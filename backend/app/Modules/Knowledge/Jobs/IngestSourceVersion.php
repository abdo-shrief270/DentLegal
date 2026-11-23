<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Jobs;

use App\Modules\Ai\AiService;
use App\Modules\Ai\AiServiceException;
use App\Modules\Knowledge\Enums\VersionStatus;
use App\Modules\Knowledge\Models\KnowledgeSourceVersion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Ingestion of one source version by ai-svc (/v1/kb/ingest, PROJECT1_PLAN.md §5.3): uploaded → processing → review
 * (chunks written with status `review`) or failed with the reason. A 40-page source takes about two minutes.
 */
final class IngestSourceVersion implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    /** @var list<int> */
    public array $backoff = [120];

    public int $timeout = 1900;

    public function __construct(public string $versionId)
    {
        $this->onQueue('ingest')->onConnection(config('dentlegal.queues.long_connection'));
    }

    public function handle(AiService $ai): void
    {
        $version = KnowledgeSourceVersion::query()->with('source')->findOrFail($this->versionId);
        if (! in_array($version->status, [VersionStatus::Uploaded, VersionStatus::Processing], true)) {
            return;
        }
        $version->update(['status' => VersionStatus::Processing, 'error' => null]);
        $pdf = Storage::disk((string) config('dentlegal.knowledge.disk'))->get((string) $version->file_key);

        try {
            $report = $ai->ingestSource((string) $pdf, [
                'version_id' => $version->id,
                'source_code' => $version->source->code,
                'source_title' => $version->source->title_ar,
                'version_label' => $version->version_label,
                'mode' => $version->requested_method,
            ]);
        } catch (AiServiceException $e) {
            if ($e->retryable && $this->attempts() < $this->tries) {
                throw $e;
            }
            $this->markFailed($e->errorCode.': '.$e->getMessage());

            return;
        }

        $method = (string) ($report['extraction_method'] ?? '');
        $version->update([
            'status' => VersionStatus::Review,
            'chunk_count' => (int) ($report['chunk_count'] ?? 0),
            'extraction_method' => in_array($method, ['text_layer', 'ocr', 'llm_transcription'], true) ? $method : null,
            'ingest_report' => array_intersect_key($report, array_flip(['chunks_by_type', 'pages', 'warnings', 'preview'])),
        ]);
    }

    public function failed(?Throwable $e): void
    {
        $this->markFailed($e instanceof AiServiceException ? $e->errorCode : 'internal_error');
    }

    private function markFailed(string $reason): void
    {
        KnowledgeSourceVersion::query()->whereKey($this->versionId)->whereIn('status', [VersionStatus::Uploaded->value, VersionStatus::Processing->value])
            ->update(['status' => VersionStatus::Failed->value, 'error' => mb_substr($reason, 0, 500), 'updated_at' => now()]);
    }
}
