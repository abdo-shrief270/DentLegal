<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * «ملاحظاتك»: general feedback from the app.
 *
 * @property string $id
 * @property string|null $user_id
 * @property string $category bug|suggestion|other
 * @property string $message
 * @property string|null $screen
 * @property string|null $app_version
 * @property string|null $platform
 * @property string $status new|seen|done
 * @property Carbon $created_at
 * @property-read User|null $user
 */
class Feedback extends Model
{
    use HasUuids;

    protected $table = 'feedback';

    protected $fillable = ['user_id', 'category', 'message', 'screen', 'app_version', 'platform', 'status'];

    protected function casts(): array
    {
        return ['message' => 'encrypted'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
