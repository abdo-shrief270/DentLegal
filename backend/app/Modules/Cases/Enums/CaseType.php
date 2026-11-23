<?php

declare(strict_types=1);

namespace App\Modules\Cases\Enums;

enum CaseType: string
{
    case Complaint = 'complaint';
    case OfficialNotice = 'official_notice';
    case ShariaBoard = 'sharia_board';
    case Preventive = 'preventive';
}
