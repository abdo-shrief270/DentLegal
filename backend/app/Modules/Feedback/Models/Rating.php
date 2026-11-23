<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * «مفيدة / غير مفيدة» on an answer, an analysis or a draft version.
 *
 * @property string $id
 * @property string $user_id
 * @property string $target_type consultation_message|analysis|draft_version
 * @property string $target_id
 * @property bool $useful
 * @property string|null $comment
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 */
class Rating extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'target_type', 'target_id', 'useful', 'comment'];

    protected function casts(): array
    {
        return ['useful' => 'boolean', 'comment' => 'encrypted'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
