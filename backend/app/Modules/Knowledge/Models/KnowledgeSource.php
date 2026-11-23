<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Models;

use App\Modules\Knowledge\Enums\SourceType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $code
 * @property string $title_ar
 * @property SourceType $type
 */
class KnowledgeSource extends Model
{
    use HasUuids;

    protected $fillable = ['code', 'title_ar', 'issuer', 'type'];

    protected function casts(): array
    {
        return ['type' => SourceType::class];
    }

    /** @return HasMany<KnowledgeSourceVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(KnowledgeSourceVersion::class, 'source_id');
    }
}
