<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One draft per case in P1; every edit is a new immutable DraftVersion.
 *
 * @property string $id
 * @property string $case_id
 * @property string $status ai_generated|in_review|approved
 * @property string|null $current_version_id
 * @property string|null $approved_version_id
 * @property string|null $reviewer_id
 * @property Carbon|null $claimed_at
 * @property Carbon|null $approved_at
 * @property-read DraftVersion|null $currentVersion
 */
class Draft extends Model
{
    use HasUuids;

    protected $fillable = ['case_id', 'analysis_id', 'status', 'current_version_id', 'approved_version_id', 'reviewer_id', 'claimed_at', 'approved_at'];

    protected function casts(): array
    {
        return ['claimed_at' => 'datetime', 'approved_at' => 'datetime'];
    }

    /** @return BelongsTo<DraftVersion, $this> */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DraftVersion::class, 'current_version_id');
    }

    /** @return HasMany<DraftVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(DraftVersion::class);
    }
}
