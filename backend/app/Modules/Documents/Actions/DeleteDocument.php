<?php

declare(strict_types=1);

namespace App\Modules\Documents\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Documents\DocumentVault;
use App\Modules\Documents\Models\Document;
use App\Modules\Identity\Models\User;
use App\Support\Api\ApiException;
use Illuminate\Support\Facades\DB;

/** DELETE /cases/{case}/documents/{document}: only before analysis starts. */
final class DeleteDocument
{
    public function __construct(private readonly DocumentVault $vault, private readonly AuditLogger $audit) {}

    public function __invoke(User $dentist, Document $document): void
    {
        DB::transaction(function () use ($dentist, $document): void {
            if (! $document->case()->lockForUpdate()->firstOrFail()->status->acceptsDocuments()) {
                throw ApiException::invalidState('لا يمكن حذف المستندات بعد بدء التحليل.');
            }
            $document->delete();
            $this->audit->record('document.deleted', $dentist, $document, ['case_id' => $document->case_id]);
        });
        $this->vault->delete($document->storage_key);
    }
}
