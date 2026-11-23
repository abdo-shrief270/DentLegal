<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Jobs;

use App\Modules\Cases\Models\DentalCase;
use App\Modules\Identity\Models\Device;
use App\Modules\Notifications\Push\PushGateway;
use App\Modules\Notifications\Push\PushResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * «رد معتمد جاهز» (PROJECT1_PLAN.md §1, §6): the one push in P1. Generic text with the case code only; the payload
 * `{type: draft_approved, case_id}` deep-links to the approved response. Dead tokens are cleared.
 */
final class SendDraftApprovedPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public function __construct(public string $caseId) {}

    public function handle(PushGateway $push): void
    {
        $case = DentalCase::query()->findOrFail($this->caseId);
        $devices = Device::query()->where('user_id', $case->dentist_id)->whereNotNull('fcm_token')->get();
        foreach ($devices as $device) {
            $result = $push->send((string) $device->fcm_token, 'رد معتمد جاهز', "اعتمد المحامي الرد على القضية {$case->code}. اضغط لعرضه.", [
                'type' => 'draft_approved', 'case_id' => $case->id,
            ]);
            if ($result === PushResult::InvalidToken) {
                $device->forceFill(['fcm_token' => null])->save();
            }
        }
    }
}
