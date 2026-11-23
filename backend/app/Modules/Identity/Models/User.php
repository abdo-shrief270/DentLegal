<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\ConsentType;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $id
 * @property string|null $name
 * @property string|null $phone_e164
 * @property string|null $email
 * @property UserStatus $status
 * @property Carbon|null $last_login_at
 * @property string|null $two_factor_secret
 * @property list<string>|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property int|null $two_factor_last_step
 * @property-read DentistProfile|null $dentistProfile
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'phone_e164', 'email', 'password', 'status', 'last_login_at'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => UserStatus::class,
            'last_login_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /** @return HasOne<DentistProfile, $this> */
    public function dentistProfile(): HasOne
    {
        return $this->hasOne(DentistProfile::class);
    }

    /** @return HasMany<Consent, $this> */
    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    /** @return HasMany<Device, $this> */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function primaryRole(): ?Role
    {
        foreach ([Role::Admin, Role::Lawyer, Role::Dentist] as $role) {
            if ($this->hasRole($role->value)) {
                return $role;
            }
        }

        return null;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function hasConsent(ConsentType $type): bool
    {
        $version = config('dentlegal.consent_versions.'.$type->value);

        return $this->consents()
            ->where('type', $type->value)
            ->where('version', $version)
            ->whereNull('revoked_at')
            ->exists();
    }

    public function needsProfile(): bool
    {
        return $this->dentistProfile?->profile_completed_at === null;
    }
}
