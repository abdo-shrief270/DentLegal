<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Immutable once written (PROJECT1_PLAN.md §3.2): a change is always a new version.
 *
 * @property string $id
 * @property string $draft_id
 * @property int $version_no
 * @property string $author_type ai|lawyer
 * @property array<string, mixed> $content_json
 * @property string|null $author_id
 * @property string|null $change_note
 * @property Carbon|null $locked_at
 * @property Carbon $created_at
 * @property-read User|null $author
 */
class DraftVersion extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = ['draft_id', 'version_no', 'author_type', 'author_id', 'content_json', 'change_note', 'ai_run_id', 'locked_at'];

    protected function casts(): array
    {
        return ['content_json' => 'encrypted:array', 'locked_at' => 'datetime', 'created_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<Draft, $this> */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class);
    }

    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            if ($version->isDirty('content_json')) {
                throw new LogicException('draft versions are immutable; save a new version instead');
            }
        });
    }
}
