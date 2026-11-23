<?php

declare(strict_types=1);

namespace App\Modules\Consultations\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * «المستشار القانوني الذكي»: one thread of questions and answers.
 *
 * @property string $id
 * @property string $user_id
 * @property string $title
 * @property array<string, mixed>|null $placeholder_state
 * @property string|null $idempotency_key
 * @property Carbon $last_message_at
 * @property Carbon $created_at
 * @property-read User $user
 */
class Consultation extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'title', 'placeholder_state', 'idempotency_key', 'last_message_at'];

    protected function casts(): array
    {
        return ['placeholder_state' => 'array', 'last_message_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ConsultationMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ConsultationMessage::class)->orderBy('id'); // UUID v7: creation order, question before its answer
    }
}
