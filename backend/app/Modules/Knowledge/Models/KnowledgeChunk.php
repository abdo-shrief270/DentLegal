<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Written by ai-svc during ingestion; immutable afterwards. The embedding and tsv columns are never loaded here.
 *
 * @property int $id
 * @property string $version_id
 * @property string $chunk_key
 * @property string $chunk_type
 * @property int|null $article_no
 * @property string|null $paragraph_ref
 * @property string $display_text
 * @property string $status
 * @property-read KnowledgeSourceVersion $version
 */
class KnowledgeChunk extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['*'];

    protected $hidden = ['embedding', 'tsv', 'search_text'];

    /** @return BelongsTo<KnowledgeSourceVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(KnowledgeSourceVersion::class, 'version_id');
    }

    /** «المادة 27» / «اللائحة 27/1» — the label shown on citation chips. */
    public function label(): string
    {
        return match (true) {
            $this->chunk_type === 'regulation' && $this->paragraph_ref !== null => 'اللائحة '.$this->paragraph_ref,
            $this->article_no !== null => 'المادة '.$this->article_no,
            default => $this->chunk_key,
        };
    }
}
