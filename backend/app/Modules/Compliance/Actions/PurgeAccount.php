<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Actions;

use App\Modules\Audit\AuditLogger;
use App\Modules\Cases\Models\DentalCase;
use App\Modules\Documents\DocumentVault;
use App\Modules\Documents\Models\Document;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The second half of «حذف الحساب» (PROJECT1_PLAN.md §9.4, §9.7): removes the account's cases with their encrypted
 * files, analyses, drafts and notes, consultations, ratings, feedback, consents, devices and profile, and empties the
 * user row. The row itself stays as a tombstone so the audit log's references stay valid; it holds no personal data.
 */
final class PurgeAccount
{
    public function __construct(private readonly DocumentVault $vault, private readonly AuditLogger $audit) {}

    /** @return array{cases: int, files: int} */
    public function __invoke(User $user): array
    {
        $caseIds = DentalCase::withTrashed()->where('dentist_id', $user->id)->pluck('id');
        $files = Document::query()->whereIn('case_id', $caseIds)->pluck('storage_key')->all();

        DB::transaction(function () use ($user, $caseIds): void {
            // Cascades take documents, pages, pipeline runs, analyses, drafts, versions and review notes.
            DentalCase::withTrashed()->whereIn('id', $caseIds)->forceDelete();
            DB::table('consultations')->where('user_id', $user->id)->delete();
            DB::table('ratings')->where('user_id', $user->id)->delete();
            DB::table('feedback')->where('user_id', $user->id)->delete();
            DB::table('consents')->where('user_id', $user->id)->delete();
            DB::table('devices')->where('user_id', $user->id)->delete();
            DB::table('dentist_profiles')->where('user_id', $user->id)->delete();
            DB::table('lawyer_profiles')->where('user_id', $user->id)->delete();
            DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('users')->where('id', $user->id)->update([
                'name' => null, 'phone_e164' => null, 'email' => null, 'password' => null, 'remember_token' => null,
                'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null,
                'purged_at' => now(),
            ]);
            $this->audit->record('account.purged', null, $user, ['cases' => $caseIds->count()]);
        });

        // Files go after the commit: a rollback must never leave rows pointing at deleted files.
        foreach ($files as $key) {
            $this->vault->delete($key);
        }

        return ['cases' => $caseIds->count(), 'files' => count($files)];
    }
}
