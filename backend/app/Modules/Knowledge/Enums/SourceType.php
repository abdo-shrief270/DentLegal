<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Enums;

enum SourceType: string
{
    case Law = 'law';
    case Regulation = 'regulation';
    case JobDescription = 'job_description';
    case Guideline = 'guideline';
}
