<?php

declare(strict_types=1);

namespace App\Modules\Cases\Models;

use App\Modules\Cases\Enums\CaseStatus;
use App\Modules\Cases\Enums\CaseType;
use App\Modules\Cases\Enums\ClaimType;
use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Models\Analysis;
use App\Modules\Pipeline\Models\Draft;
use Database\Factories\DentalCaseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A dentist's case. Named DentalCase because `case` is a PHP keyword; the table is `cases`.
 *
 * @property string $id
 * @property string $code
 * @property string $dentist_id
 * @property CaseType $case_type
 * @property ClaimType $claim_type
 * @property CaseStatus $status
 * @property string $facts_masked
 * @property array<string, int> $facts_entity_counts
 * @property Carbon $received_on
 * @property Carbon $procedure_date
 * @property Carbon|null $submitted_for_review_at
 * @property Carbon|null $approved_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DentalCase extends Model
{
    /** @use HasFactory<DentalCaseFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'cases';

    protected $fillable = [
        'code', 'dentist_id', 'case_type', 'authority', 'complaint_no', 'received_on', 'title', 'procedure',
        'procedure_date', 'patient_code', 'claim_type', 'facts_masked', 'facts_entity_counts', 'status',
    ];

    protected $attributes = ['status' => 'draft', 'facts_entity_counts' => '{}'];

    protected function casts(): array
    {
        return [
            'case_type' => CaseType::class,
            'claim_type' => ClaimType::class,
            'status' => CaseStatus::class,
            'facts_masked' => 'encrypted',
            'facts_entity_counts' => 'array',
            'received_on' => 'date',
            'procedure_date' => 'date',
            'submitted_for_review_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    protected static function newFactory(): DentalCaseFactory
    {
        return DentalCaseFactory::new();
    }

    /** @return BelongsTo<User, $this> */
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    /** @return HasOne<Analysis, $this> */
    public function currentAnalysis(): HasOne
    {
        return $this->hasOne(Analysis::class, 'case_id')->where('is_current', true);
    }

    /** @return HasOne<Draft, $this> */
    public function draft(): HasOne
    {
        return $this->hasOne(Draft::class, 'case_id');
    }
}
