<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Models;

use App\Modules\Cases\Models\DentalCase;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $case_id
 * @property int $position_strength
 * @property string $risk_level
 * @property array<string, mixed> $result_json
 * @property list<int> $source_chunk_ids
 * @property bool $sources_sufficient
 * @property bool $needs_attention
 * @property bool $is_current
 * @property Carbon $created_at
 * @property-read DentalCase $case
 */
class Analysis extends Model
{
    use HasUuids;

    protected $fillable = ['case_id', 'pipeline_job_id', 'ai_run_id', 'position_strength', 'risk_level', 'result_json', 'source_chunk_ids', 'sources_sufficient', 'needs_attention', 'is_current'];

    protected function casts(): array
    {
        return ['result_json' => 'encrypted:array', 'source_chunk_ids' => 'array', 'sources_sufficient' => 'boolean', 'needs_attention' => 'boolean', 'is_current' => 'boolean'];
    }

    /** @return BelongsTo<DentalCase, $this> */
    public function case(): BelongsTo
    {
        return $this->belongsTo(DentalCase::class, 'case_id');
    }
}
