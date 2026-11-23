<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use App\Modules\Identity\Enums\ConsentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RecordConsentsRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'consents' => ['required', 'array', 'min:1', 'max:4'],
            'consents.*.type' => ['required', Rule::enum(ConsentType::class), 'distinct'],
            'consents.*.version' => ['required', 'string', 'max:16'],
        ];
    }
}
