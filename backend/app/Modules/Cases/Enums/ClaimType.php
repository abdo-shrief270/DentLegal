<?php

declare(strict_types=1);

namespace App\Modules\Cases\Enums;

enum ClaimType: string
{
    case Compensation = 'compensation';
    case Disciplinary = 'disciplinary';
    case Unknown = 'unknown';
}
