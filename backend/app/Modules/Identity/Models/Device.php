<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\Platform;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $user_id
 * @property int|null $token_id
 * @property Platform $platform
 * @property string|null $fcm_token
 */
class Device extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'token_id', 'platform', 'name', 'app_version', 'fcm_token', 'last_seen_at'];

    protected $hidden = ['fcm_token'];

    protected function casts(): array
    {
        return ['platform' => Platform::class, 'last_seen_at' => 'datetime', 'fcm_token' => 'encrypted'];
    }
}
