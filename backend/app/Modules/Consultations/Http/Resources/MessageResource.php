<?php

declare(strict_types=1);

namespace App\Modules\Consultations\Http\Resources;

use App\Modules\Consultations\Models\ConsultationMessage;
use App\Modules\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A question or an answer. The dentist's own name and licence, masked before the AI saw them, are shown back.
 *
 * @mixin ConsultationMessage
 */
final class MessageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $fill = fn (mixed $value): mixed => self::fill($value, [
            '{{DENTIST_NAME}}' => $user->name ?? 'الممارس الصحي',
            '{{DENTIST_LICENSE}}' => $user->dentistProfile->scfhs_license_no ?? '—',
        ]);

        if ($this->role === 'user') {
            return ['id' => $this->id, 'role' => 'user', 'text' => $fill($this->text_masked), 'created_at' => $this->created_at->toIso8601ZuluString()];
        }

        return [
            'id' => $this->id,
            'role' => 'assistant',
            'reply_to' => $this->reply_to_id,
            'consultation_id' => $this->consultation_id,
            'status' => $this->status,
            'error' => $this->error_code,
            'answer' => $this->answer_json === null ? null : $fill($this->answer_json),
            'created_at' => $this->created_at->toIso8601ZuluString(),
            'answered_at' => $this->answered_at?->toIso8601ZuluString(),
        ];
    }

    /** @param array<string, string> $map */
    private static function fill(mixed $value, array $map): mixed
    {
        if (is_string($value)) {
            return strtr($value, $map);
        }
        if (is_array($value)) {
            return array_map(fn (mixed $v): mixed => self::fill($v, $map), $value);
        }

        return $value;
    }
}
