<?php

declare(strict_types=1);

namespace App\Modules\Review\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Cases\Actions\TransitionCase;
use App\Modules\Cases\Enums\CaseStatus;
use App\Modules\Identity\Models\User;
use App\Modules\Pipeline\Models\Draft;
use App\Modules\Pipeline\Models\DraftVersion;
use App\Modules\Review\Events\DraftApproved;
use App\Support\Api\ApiException;
use Illuminate\Support\Facades\DB;

/**
 * «اعتماد»: locks the current version, approves the draft and moves the case to `approved` in one transaction, then
 * tells the dentist (after commit). Approving with unverified citations or unsupported legal claims needs
 * `confirm_warnings`, so the lawyer decides with the checks in front of them.
 */
final class ApproveDraft
{
    public function __construct(private readonly TransitionCase $transition, private readonly AuditLogger $audit) {}

    public function __invoke(User $lawyer, Draft $draft, bool $confirmWarnings): Draft
    {
        $approved = DB::transaction(function () use ($lawyer, $draft, $confirmWarnings): Draft {
            /** @var Draft $locked */
            $locked = Draft::query()->with('case')->lockForUpdate()->findOrFail($draft->id);
            ReviewGuard::assertClaimedBy($locked, $lawyer);
            /** @var DraftVersion $version */
            $version = DraftVersion::query()->findOrFail($locked->current_version_id);

            $warnings = self::warnings($version->content_json);
            if ($warnings > 0 && ! $confirmWarnings) {
                throw new ApiException(422, 'warnings_unconfirmed', 'في المسودة استشهادات غير موثقة أو دفوع غير مسندة. راجعها أو أكّد الاعتماد.', ['warnings' => $warnings]);
            }

            $version->update(['locked_at' => now()]);
            $locked->update(['status' => 'approved', 'approved_version_id' => $version->id, 'approved_at' => now()]);
            ($this->transition)($locked->case, CaseStatus::Approved, ['draft_id' => $locked->id]);
            $this->audit->record('review.approved', $lawyer, $locked, ['version_no' => $version->version_no, 'warnings' => $warnings]);

            return $locked;
        });
        DraftApproved::dispatch($approved->id, $approved->case_id, $approved->case->dentist_id);

        return $approved;
    }

    /** @param array<string, mixed> $content */
    public static function warnings(array $content): int
    {
        $count = 0;
        foreach ([$content['intro'] ?? [], ...array_merge(...array_map(fn (array $s): array => $s['paragraphs'] ?? [], $content['sections'] ?? []))] as $p) {
            $count += (int) ($p['unsupported'] ?? false);
            foreach ($p['citations'] ?? [] as $c) {
                $count += (int) ! ($c['verified'] ?? false);
            }
        }

        return $count;
    }
}
