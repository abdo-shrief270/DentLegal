<?php

declare(strict_types=1);

namespace App\Modules\Identity\Enums;

enum Role: string
{
    case Dentist = 'dentist';
    case Lawyer = 'lawyer';
    case Admin = 'admin';
}
