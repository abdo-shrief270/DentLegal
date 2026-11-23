<?php

declare(strict_types=1);

namespace App\Modules\Documents\Models;

use App\Modules\Cases\Models\DentalCase;
use App\Modules\Documents\Enums\DocumentKind;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $case_id
 * @property DocumentKind $kind
 * @property string $original_name
 * @property string $mime
 * @property int $size_bytes
 * @property string $storage_key
 * @property string $wrapped_key
 * @property string $sha256
 * @property int|null $page_count
 * @property string $ocr_status
 * @property string|null $ocr_method
 * @property float|null $ocr_confidence
 * @property bool $low_quality
 * @property Carbon $created_at
 * @property-read DentalCase $case
 */
class Document extends Model
{
    use HasUuids;

    protected $fillable = [
        'case_id', 'kind', 'original_name', 'mime', 'size_bytes', 'storage_key', 'wrapped_key', 'sha256', 'page_count',
        'ocr_status', 'ocr_method', 'ocr_confidence', 'low_quality',
    ];

    protected $hidden = ['storage_key', 'wrapped_key'];

    protected function casts(): array
    {
        return ['kind' => DocumentKind::class, 'low_quality' => 'boolean', 'ocr_confidence' => 'float', 'size_bytes' => 'integer'];
    }

    /** @return BelongsTo<DentalCase, $this> */
    public function case(): BelongsTo
    {
        return $this->belongsTo(DentalCase::class, 'case_id');
    }

    /** @return HasMany<DocumentPage, $this> */
    public function pages(): HasMany
    {
        return $this->hasMany(DocumentPage::class)->orderBy('page_no');
    }
}
