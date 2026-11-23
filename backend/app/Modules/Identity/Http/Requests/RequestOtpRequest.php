<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;

final class RequestOtpRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['phone' => ['required', 'string', 'max:20', function (string $attr, mixed $value, \Closure $fail): void {
            if (Phone::normalizeSaudiMobile((string) $value) === null) {
                $fail('أدخل رقم جوال سعودي صحيح يبدأ بـ 5.');
            }
        }]];
    }

    public function phone(): string
    {
        return (string) Phone::normalizeSaudiMobile($this->string('phone')->toString());
    }
}
