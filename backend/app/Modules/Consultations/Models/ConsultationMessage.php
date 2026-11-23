<?php

declare(strict_types=1);

namespace App\Modules\Consultations\Models;

use App\Modules\Pipeline\Models\AiRun;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A question (role user, text_masked) or the answer to it (role assistant, answer_json). The answer row exists
 * from the moment the question is asked, as `pending`, so the app can poll it by id.
 *
 * @property string $id
 * @property string $consultation_id
 * @property string $role user|assistant
 * @property string|null $reply_to_id
 * @property string|null $text_masked
 * @property array<string, mixed>|null $answer_json
 * @property string|null $status pending|answered|refused|failed (answers only)
 * @property string|null $error_code
 * @property int|null $ai_run_id
 * @property Carbon|null $answered_at
 * @property Carbon $created_at
 * @property-read Consultation $consultation
 * @property-read ConsultationMessage|null $question
 */
class ConsultationMessage extends Model
{
    use HasUuids;

    protected $fillable = ['consultation_id', 'role', 'reply_to_id', 'text_masked', 'answer_json', 'status', 'error_code', 'idempotency_key', 'ai_run_id', 'answered_at'];

    protected $hidden = ['idempotency_key'];

    protected function casts(): array
    {
        return ['text_masked' => 'encrypted', 'answer_json' => 'encrypted:array', 'answered_at' => 'datetime'];
    }

    /** @return BelongsTo<Consultation, $this> */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /** @return BelongsTo<ConsultationMessage, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    /** @return BelongsTo<AiRun, $this> */
    public function aiRun(): BelongsTo
    {
        return $this->belongsTo(AiRun::class);
    }
}
