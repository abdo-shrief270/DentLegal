<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Models;

use App\Modules\Cases\Models\DentalCase;
use App\Modules\Pipeline\Enums\PipelineStep;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One run of the case pipeline. Drives GET /cases/{id}/analysis/status.
 *
 * @property string $id
 * @property string $case_id
 * @property string $status queued|running|succeeded|failed
 * @property string|null $current_step
 * @property array<string, array{status: string, ms: int|null}> $steps
 * @property array<string, mixed>|null $placeholder_state
 * @property string|null $error_code
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property-read DentalCase $case
 */
class PipelineJob extends Model
{
    use HasUuids;

    protected $fillable = ['case_id', 'type', 'status', 'current_step', 'steps', 'placeholder_state', 'idempotency_key', 'error_code', 'started_at', 'finished_at'];

    protected function casts(): array
    {
        return ['steps' => 'array', 'placeholder_state' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    /** @return BelongsTo<DentalCase, $this> */
    public function case(): BelongsTo
    {
        return $this->belongsTo(DentalCase::class, 'case_id');
    }

    public function stepStarted(PipelineStep $step): void
    {
        $steps = $this->steps;
        $steps[$step->value]['status'] = 'running';
        $this->forceFill(['steps' => $steps, 'current_step' => $step->value, 'status' => 'running', 'started_at' => $this->started_at ?? now()])->save();
    }

    public function stepDone(PipelineStep $step, ?int $ms): void
    {
        $steps = $this->steps;
        $steps[$step->value] = ['status' => 'done', 'ms' => $ms];
        $this->forceFill(['steps' => $steps])->save();
    }

    public function progress(): int
    {
        $done = count(array_filter($this->steps, fn (array $s): bool => $s['status'] === 'done'));

        return (int) round($done / max(1, count($this->steps)) * 100);
    }
}
