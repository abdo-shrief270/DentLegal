<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $user_id
 * @property string $license_no
 * @property string|null $firm_name
 */
class LawyerProfile extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['user_id', 'license_no', 'firm_name'];
}
