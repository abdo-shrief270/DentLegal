<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $user_id
 * @property string $specialty
 * @property string $city
 * @property string $facility_name
 * @property string|null $scfhs_license_no
 * @property Carbon|null $profile_completed_at
 */
class DentistProfile extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['user_id', 'specialty', 'city', 'facility_name', 'scfhs_license_no', 'profile_completed_at'];

    protected function casts(): array
    {
        return ['profile_completed_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
