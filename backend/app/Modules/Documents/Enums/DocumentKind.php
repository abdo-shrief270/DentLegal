<?php

declare(strict_types=1);

namespace App\Modules\Documents\Enums;

enum DocumentKind: string
{
    case ComplaintLetter = 'complaint_letter';
    case MedicalRecord = 'medical_record';
    case ConsentForm = 'consent_form';
    case Radiology = 'radiology';
    case Referral = 'referral';
    case Insurance = 'insurance';
    case Other = 'other';
}
