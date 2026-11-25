<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One Claude call (PROJECT1_PLAN.md §5.13). No prompt or output text is stored. */
class AiRun extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['retrieval_params' => 'array', 'fallback_used' => 'boolean', 'repaired' => 'boolean', 'cost_usd' => 'float', 'citation_validity' => 'float'];
    }

    /** @return HasMany<AiRunSource, $this> */
    public function sources(): HasMany
    {
        return $this->hasMany(AiRunSource::class, 'ai_run_id');
    }
}
