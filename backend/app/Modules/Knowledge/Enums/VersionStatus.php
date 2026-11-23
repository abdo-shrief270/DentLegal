<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Enums;

/** uploaded → processing → review → active → archived (+ failed). §5.3 */
enum VersionStatus: string
{
    case Uploaded = 'uploaded';
    case Processing = 'processing';
    case Review = 'review';
    case Active = 'active';
    case Archived = 'archived';
    case Failed = 'failed';
}
