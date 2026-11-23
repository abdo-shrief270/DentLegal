<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProfileRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'specialty' => ['required', 'string', 'max:80'],
            'city' => ['required', 'string', 'max:60'],
            'facility_name' => ['required', 'string', 'max:160'],
            'scfhs_license_no' => ['nullable', 'string', 'max:40', 'regex:/^[0-9A-Za-z\-]+$/'],
        ];
    }
}
