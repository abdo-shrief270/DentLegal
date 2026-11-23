<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property Role $role
 * @property string|null $name
 * @property string|null $phone_e164
 * @property Carbon $expires_at
 * @property string|null $email
 * @property string|null $token_hash
 * @property Carbon|null $accepted_at
 * @property Carbon|null $revoked_at
 * @property array<string, string|null>|null $meta
 * @property int $sent_count
 * @property Carbon|null $last_sent_at
 * @property Carbon $created_at
 */
class Invitation extends Model
{
    use HasUuids;

    protected $fillable = ['role', 'name', 'phone_e164', 'email', 'token_hash', 'invited_by', 'expires_at', 'accepted_at', 'revoked_at', 'meta', 'sent_count', 'last_sent_at'];

    protected function casts(): array
    {
        return ['role' => Role::class, 'expires_at' => 'datetime', 'accepted_at' => 'datetime', 'revoked_at' => 'datetime', 'meta' => 'array', 'last_sent_at' => 'datetime'];
    }

    /** @param Builder<self> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '>', now());
    }
}
