<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Models;

use App\Modules\Knowledge\Enums\VersionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $source_id
 * @property string $version_label
 * @property string|null $edition
 * @property VersionStatus $status
 * @property int $chunk_count
 * @property-read KnowledgeSource $source
 */
class KnowledgeSourceVersion extends Model
{
    use HasUuids;

    protected $fillable = [
        'source_id', 'version_label', 'edition', 'effective_from', 'file_key', 'sha256', 'extraction_method',
        'status', 'chunk_count', 'uploaded_by', 'published_by', 'published_at', 'archived_at', 'error',
    ];

    protected function casts(): array
    {
        return [
            'status' => VersionStatus::class,
            'effective_from' => 'date',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<KnowledgeSource, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(KnowledgeSource::class, 'source_id');
    }

    /** @return HasMany<KnowledgeChunk, $this> */
    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'version_id');
    }
}
