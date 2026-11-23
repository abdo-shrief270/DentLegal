<?php

declare(strict_types=1);

namespace App\Modules\Review\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** A lawyer-edited DraftContent (same shape as the AI draft). */
final class SaveVersionRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $paragraph = fn (string $p): array => [
            "{$p}" => ['required', 'array'],
            "{$p}.id" => ['nullable', 'string', 'max:20'],
            "{$p}.text" => ['required', 'string', 'min:2', 'max:4000'],
            "{$p}.legal_claim" => ['required', 'boolean'],
            "{$p}.citations" => ['present', 'array', 'max:10'],
            "{$p}.citations.*.ref" => ['required', 'string', 'regex:/^[SD]\d{1,2}$/'],
            "{$p}.citations.*.quote" => ['required', 'string', 'min:3', 'max:400'],
        ];

        return [
            'content' => ['required', 'array'],
            'content.title' => ['required', 'string', 'min:5', 'max:300'],
            'content.addressee' => ['required', 'string', 'max:400'],
            'content.salutation' => ['present', 'nullable', 'string', 'max:200'],
            'content.closing' => ['present', 'nullable', 'string', 'max:500'],
            'content.attachments' => ['present', 'array', 'max:20'],
            'content.attachments.*' => ['string', 'max:200'],
            ...$paragraph('content.intro'),
            'content.sections' => ['required', 'array', 'min:1', 'max:3'],
            'content.sections.*.kind' => ['required', 'in:facts,defenses,requests'],
            'content.sections.*.heading' => ['required', 'string', 'max:120'],
            'content.sections.*.paragraphs' => ['required', 'array', 'min:1', 'max:20'],
            ...$paragraph('content.sections.*.paragraphs.*'),
            'note' => ['nullable', 'string', 'max:300'],
        ];
    }
}
