<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Console;

use App\Modules\Compliance\Actions\PurgeAccount;
use App\Modules\Identity\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Nightly retention per PROJECT1_PLAN.md §9.7 (D18 confirms the case-data rule at the end of the pilot):
 * OTP codes 24 h, tokens after 30 days unused, deleted accounts purged after the grace period, ai_runs 12 months,
 * audit log 2 years. Case data otherwise stays until the dentist deletes the account.
 */
final class PurgeExpiredData extends Command
{
    protected $signature = 'retention:purge {--dry-run : count what would be removed, change nothing}';

    protected $description = 'Delete data past its retention period (PROJECT1_PLAN.md §9.7)';

    public function handle(PurgeAccount $purge): int
    {
        $dry = (bool) $this->option('dry-run');
        $r = config('dentlegal.retention');
        $now = now();

        $counts = [
            'otp_codes' => $this->sweep(DB::table('otp_codes')->where('created_at', '<', $now->copy()->subHours((int) $r['otp_hours'])), $dry),
            'tokens' => $this->sweep(DB::table('personal_access_tokens')->where(fn ($q) => $q
                ->where('expires_at', '<', $now)
                ->orWhere(fn ($q) => $q->whereNotNull('last_used_at')->where('last_used_at', '<', $now->copy()->subDays((int) $r['token_idle_days'])))
                ->orWhere(fn ($q) => $q->whereNull('last_used_at')->where('created_at', '<', $now->copy()->subDays((int) $r['token_idle_days'])))), $dry),
            'ai_runs' => $this->sweep(DB::table('ai_runs')->where('created_at', '<', $now->copy()->subMonths((int) $r['ai_runs_months'])), $dry),
            'audit_logs' => $this->sweep(DB::table('audit_logs')->where('occurred_at', '<', $now->copy()->subYears((int) $r['audit_years'])), $dry),
        ];

        $accounts = User::onlyTrashed()->whereNull('purged_at')->where('deleted_at', '<', $now->copy()->subDays((int) $r['account_grace_days']))->get();
        $counts['accounts'] = $accounts->count();
        if (! $dry) {
            foreach ($accounts as $user) {
                $purge($user);
            }
        }

        foreach ($counts as $what => $n) {
            $this->line(sprintf('%-12s %d%s', $what, $n, $dry ? ' (dry run)' : ''));
        }

        return self::SUCCESS;
    }

    private function sweep(Builder $query, bool $dry): int
    {
        return $dry ? $query->count() : $query->delete();
    }
}
