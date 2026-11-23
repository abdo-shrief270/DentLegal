<?php

declare(strict_types=1);

namespace App\Modules\Consultations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AskQuestionRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['question' => ['required', 'string', 'min:3', 'max:2000']];
    }

    public function idempotencyKey(): ?string
    {
        $key = $this->header('Idempotency-Key');

        return is_string($key) && $key !== '' ? mb_substr($key, 0, 100) : null;
    }
}
