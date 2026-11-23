<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Console;

use App\Modules\Feedback\Support\UsageStats;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/** Nightly roll-up of yesterday's usage into daily_usage_stats (S6.3). `--days=N` back-fills. */
final class RollUpUsageStats extends Command
{
    protected $signature = 'stats:roll-up {--days=1 : how many past days to (re)compute}';

    protected $description = 'Roll up daily usage metrics for the admin dashboard';

    public function handle(UsageStats $stats): int
    {
        $days = max(1, min(400, (int) $this->option('days')));
        for ($i = $days; $i >= 1; $i--) {
            $day = Carbon::today()->subDays($i);
            $stats->rollUp($day);
            $this->line($day->toDateString());
        }

        return self::SUCCESS;
    }
}
