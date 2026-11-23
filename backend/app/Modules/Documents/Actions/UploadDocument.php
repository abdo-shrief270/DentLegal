<?php

declare(strict_types=1);

namespace App\Modules\Documents\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Cases\Models\DentalCase;
use App\Modules\Documents\DocumentVault;
use App\Modules\Documents\Enums\DocumentKind;
use App\Modules\Documents\Models\Document;
use App\Modules\Identity\Models\User;
use App\Support\Api\ApiException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** POST /cases/{case}/documents (S3.2). The file is checked by its bytes, hashed, encrypted, and only then stored. */
final class UploadDocument
{
    public const MAX_BYTES = 20 * 1024 * 1024;

    public const MAX_PER_CASE = 15;

    private const MAGIC = [
        'application/pdf' => '%PDF-',
        'image/jpeg' => "\xFF\xD8\xFF",
        'image/png' => "\x89PNG\r\n\x1a\n",
    ];

    public function __construct(private readonly DocumentVault $vault, private readonly AuditLogger $audit) {}

    public function __invoke(User $dentist, DentalCase $case, UploadedFile $file, DocumentKind $kind): Document
    {
        $contents = (string) file_get_contents($file->getRealPath());
        if (strlen($contents) > self::MAX_BYTES) {
            throw new ApiException(413, 'file_too_large', 'حجم الملف أكبر من 20 ميجابايت.');
        }
        $mime = $this->sniff($contents);

        return DB::transaction(function () use ($dentist, $case, $file, $kind, $contents, $mime): Document {
            /** @var DentalCase $locked */
            $locked = DentalCase::query()->whereKey($case->id)->lockForUpdate()->firstOrFail();
            if (! $locked->status->acceptsDocuments()) {
                throw ApiException::invalidState('لا يمكن إضافة مستندات بعد بدء التحليل.');
            }
            if (Document::query()->where('case_id', $case->id)->count() >= self::MAX_PER_CASE) {
                throw new ApiException(413, 'too_many_files', 'الحد الأقصى 15 مستندًا لكل قضية.');
            }

            $id = (string) Str::uuid7();
            $stored = $this->vault->store($id, $contents);
            // The id is bound into the ciphertext, so it is set by us, not by HasUuids.
            DB::afterRollBack(fn () => $this->vault->delete($stored['storage_key']));
            $document = (new Document)->forceFill([
                'id' => $id,
                'case_id' => $case->id,
                'kind' => $kind,
                'original_name' => mb_substr($file->getClientOriginalName() ?: 'document', 0, 200),
                'mime' => $mime,
                'size_bytes' => strlen($contents),
                'sha256' => hash('sha256', $contents),
                ...$stored,
            ]);
            $document->save();
            $this->audit->record('document.uploaded', $dentist, $document, ['case_id' => $case->id, 'kind' => $kind->value, 'mime' => $mime]);

            return $document;
        });
    }

    private function sniff(string $contents): string
    {
        foreach (self::MAGIC as $mime => $magic) {
            if (str_starts_with($contents, $magic)) {
                return $mime;
            }
        }
        throw new ApiException(415, 'unsupported_media_type', 'يقبل النظام ملفات PDF وصور JPEG وPNG فقط.');
    }
}
