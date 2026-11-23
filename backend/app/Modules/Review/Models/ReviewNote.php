<?php

declare(strict_types=1);

namespace App\Modules\Review\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $draft_id
 * @property string|null $author_id
 * @property string $text
 * @property Carbon $created_at
 * @property-read User|null $author
 */
class ReviewNote extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = ['draft_id', 'author_id', 'text'];

    protected function casts(): array
    {
        return ['text' => 'encrypted', 'created_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
