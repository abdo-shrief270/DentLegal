<?php

declare(strict_types=1);

namespace App\Modules\Citations\Models;

use App\Modules\Knowledge\Models\KnowledgeChunk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $citable_type consultation_message|analysis|draft_version
 * @property string $citable_id
 * @property string $route consult|analysis|draft
 * @property string|null $case_id
 * @property string $item_path
 * @property string $source_kind knowledge|case_document
 * @property string $ref
 * @property int|null $chunk_id
 * @property string $claim
 * @property string $quote
 * @property bool $verified
 * @property Carbon|null $sampled_at
 * @property string|null $lawyer_verdict correct|incorrect
 * @property string|null $lawyer_note
 * @property Carbon|null $reviewed_at
 * @property-read KnowledgeChunk|null $chunk
 */
class Citation extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'claim' => 'encrypted', 'quote' => 'encrypted', 'lawyer_note' => 'encrypted', 'verified' => 'boolean',
            'verification' => 'array', 'sampled_at' => 'datetime', 'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<KnowledgeChunk, $this> */
    public function chunk(): BelongsTo
    {
        return $this->belongsTo(KnowledgeChunk::class, 'chunk_id');
    }
}
