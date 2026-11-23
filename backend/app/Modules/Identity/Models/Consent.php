<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\ConsentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property ConsentType $type
 * @property string $version
 * @property Carbon $granted_at
 * @property Carbon|null $revoked_at
 */
class Consent extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'type', 'version', 'granted_at', 'revoked_at', 'ip'];

    protected function casts(): array
    {
        return ['type' => ConsentType::class, 'granted_at' => 'datetime', 'revoked_at' => 'datetime'];
    }
}
