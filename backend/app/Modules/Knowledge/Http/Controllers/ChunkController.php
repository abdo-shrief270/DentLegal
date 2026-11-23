<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Http\Controllers;

use App\Modules\Knowledge\Models\KnowledgeChunk;
use Illuminate\Http\JsonResponse;

final class ChunkController
{
    /**
     * Source text for the «نص المادة» sheet. Archived chunks stay readable, because old answers and drafts cite them.
     */
    public function show(int $chunk): JsonResponse
    {
        /** @var KnowledgeChunk $model */
        $model = KnowledgeChunk::query()
            ->select(['id', 'version_id', 'chunk_key', 'chunk_type', 'article_no', 'paragraph_ref', 'display_text', 'status'])
            ->with('version:id,source_id,version_label,edition,status', 'version.source:id,code,title_ar')
            ->findOrFail($chunk);

        return response()->json(['data' => [
            'id' => $model->id,
            'label' => $model->label(),
            'law' => $model->version->source->title_ar,
            'source_code' => $model->version->source->code,
            'edition' => $model->version->edition,
            'text' => $model->display_text,
            'is_current' => $model->status === 'active',
        ]])->setCache(['private' => true, 'max_age' => 3600]);
    }
}
