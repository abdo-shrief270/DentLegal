<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateDeviceRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'fcm_token' => ['nullable', 'string', 'max:4096'],
            'app_version' => ['nullable', 'string', 'max:32'],
            'name' => ['nullable', 'string', 'max:80'],
        ];
    }
}
