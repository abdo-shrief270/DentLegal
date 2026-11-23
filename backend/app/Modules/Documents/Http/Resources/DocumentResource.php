<?php

declare(strict_types=1);

namespace App\Modules\Documents\Http\Resources;

use App\Modules\Documents\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Document */
final class DocumentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind->value,
            'name' => $this->original_name,
            'mime' => $this->mime,
            'size_bytes' => $this->size_bytes,
            'page_count' => $this->page_count,
            'ocr_status' => $this->ocr_status,
            'low_quality' => $this->low_quality,
            'encrypted' => true,
            'created_at' => $this->created_at->toIso8601ZuluString(),
        ];
    }
}
