<?php

declare(strict_types=1);

namespace App\Modules\Cases\Http\Requests;

use App\Modules\Cases\Enums\CaseType;
use App\Modules\Cases\Enums\ClaimType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCaseRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'case_type' => ['required', Rule::enum(CaseType::class)],
            'authority' => ['required', 'string', 'max:200'],
            'complaint_no' => ['required', 'string', 'max:60'],
            'received_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'title' => ['required', 'string', 'min:5', 'max:160'],
            'procedure' => ['required', 'string', 'max:200'],
            'procedure_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            // An internal code from the dentist's own records, never the patient's name or ID.
            'patient_code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-_\/]+$/'],
            'claim_type' => ['required', Rule::enum(ClaimType::class)],
            'facts' => ['required', 'string', 'min:20', 'max:8000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['patient_code.regex' => 'استخدم رمزًا داخليًا (حروف لاتينية وأرقام) وليس اسم المريض أو هويته.'];
    }
}
