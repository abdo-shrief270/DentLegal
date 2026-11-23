<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use App\Modules\Identity\Enums\Platform;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class VerifyOtpRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
            'device' => ['required', 'array'],
            'device.platform' => ['required', Rule::in([Platform::Ios->value, Platform::Android->value])],
            'device.name' => ['nullable', 'string', 'max:80'],
            'device.app_version' => ['nullable', 'string', 'max:32'],
        ];
    }

    public function phone(): string
    {
        return Phone::normalizeSaudiMobile($this->string('phone')->toString()) ?? '';
    }
}
