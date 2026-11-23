<?php

declare(strict_types=1);

namespace App\Modules\Identity\Enums;

enum UserStatus: string
{
    case Invited = 'invited';
    case Active = 'active';
    case Disabled = 'disabled';
    case Deleted = 'deleted';
}
