<?php

declare(strict_types=1);

namespace App\Modules\Documents\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Masked text of one page. Raw OCR text is never stored.
 *
 * @property string $id
 * @property string $document_id
 * @property int $page_no
 * @property string $text_masked
 * @property float $confidence
 * @property string $method
 * @property array<string, int> $masked_entity_counts
 */
class DocumentPage extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['document_id', 'page_no', 'text_masked', 'confidence', 'method', 'masked_entity_counts'];

    protected function casts(): array
    {
        return ['text_masked' => 'encrypted', 'confidence' => 'float', 'masked_entity_counts' => 'array', 'page_no' => 'integer'];
    }
}
