<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Daily AI health digest (PROJECT1_PLAN.md §8.6): the last 24 hours of refusals, citation validity, cost and failures,
 * with flags against the thresholds in config/dentlegal.php. Emailed to AI_HEALTH_RECIPIENTS every day (a quiet day
 * says so); a flagged day also goes to ALERT_WEBHOOK_URL. Counts only: no question, answer or case text.
 */
final class AiHealthDigest extends Command
{
    protected $signature = 'ai:health-digest {--hours=24 : window ending now}';

    protected $description = 'Email the daily AI health digest (refusals, citation validity, cost, failures)';

    public function handle(): int
    {
        $c = (array) config('dentlegal.ai_health');
        $to = now();
        $from = $to->copy()->subHours(max(1, (int) $this->option('hours')));
        $m = self::measure($from, $to);

        $flags = [];
        if ((int) $c['min_answers'] <= $m['answers'] + $m['refusals'] && $m['refusal_rate'] !== null && $m['refusal_rate'] > (float) $c['refusal_rate_max']) {
            $flags[] = sprintf('نسبة الاعتذار %s%% أعلى من الحد %s%%', self::pct($m['refusal_rate']), self::pct((float) $c['refusal_rate_max']));
        }
        if ($m['citation_validity'] !== null && $m['validated_runs'] >= 3 && $m['citation_validity'] < (float) $c['citation_validity_min']) {
            $flags[] = sprintf('صحة الاستشهادات %s%% أقل من الحد %s%%', self::pct($m['citation_validity']), self::pct((float) $c['citation_validity_min']));
        }
        if ($m['cost_usd'] > (float) $c['daily_cost_max_usd']) {
            $flags[] = sprintf('التكلفة $%.2f أعلى من الحد $%.2f', $m['cost_usd'], (float) $c['daily_cost_max_usd']);
        }
        if ($m['failed_runs'] > 0) {
            $flags[] = "{$m['failed_runs']} طلبات فاشلة إلى الذكاء الاصطناعي";
        }
        if ($m['failed_jobs'] > 0) {
            $flags[] = "{$m['failed_jobs']} مهام قضايا فشلت";
        }

        $body = $this->body($from, $to, $m, $flags);
        $subject = ($flags === [] ? 'DentLegal: صحة الذكاء الاصطناعي — سليمة' : 'DentLegal: تنبيه صحة الذكاء الاصطناعي — '.count($flags).' ملاحظات');
        $recipients = (array) $c['recipients'];
        if ($recipients !== []) {
            Mail::raw($body, fn ($mail) => $mail->to($recipients)->subject($subject));
        }
        if ($flags !== [] && filled($c['webhook_url'])) {
            Http::timeout(10)->post((string) $c['webhook_url'], ['text' => $subject."\n".implode("\n", $flags)]);
        }
        Log::info('ai.health_digest', ['flags' => count($flags), ...$m]);
        $this->line($body);

        return self::SUCCESS;
    }

    /** @return array{answers: int, refusals: int, refusal_rate: float|null, citation_validity: float|null, validated_runs: int, cost_usd: float, runs: int, failed_runs: int, fallbacks: int, failed_jobs: int, cases_drafted: int} */
    public static function measure(Carbon $from, Carbon $to): array
    {
        $answers = (int) DB::table('consultation_messages')->where('role', 'assistant')->where('status', 'answered')->whereBetween('answered_at', [$from, $to])->count();
        $refusals = (int) DB::table('consultation_messages')->where('role', 'assistant')->where('status', 'refused')->whereBetween('answered_at', [$from, $to])->count();
        $runs = DB::table('ai_runs')->whereBetween('created_at', [$from, $to])->selectRaw(
            'count(*) as runs, coalesce(sum(cost_usd), 0) as cost, avg(citation_validity) as validity, count(citation_validity) as validated,
             count(*) filter (where error_code is not null) as failed, count(*) filter (where fallback_used) as fallbacks'
        )->first();

        return [
            'answers' => $answers,
            'refusals' => $refusals,
            'refusal_rate' => $answers + $refusals > 0 ? round($refusals / ($answers + $refusals), 3) : null,
            'citation_validity' => $runs?->validity === null ? null : round((float) $runs->validity, 3),
            'validated_runs' => (int) ($runs->validated ?? 0),
            'cost_usd' => round((float) ($runs->cost ?? 0), 2),
            'runs' => (int) ($runs->runs ?? 0),
            'failed_runs' => (int) ($runs->failed ?? 0),
            'fallbacks' => (int) ($runs->fallbacks ?? 0),
            'failed_jobs' => (int) DB::table('pipeline_jobs')->where('status', 'failed')->whereBetween('updated_at', [$from, $to])->count(),
            'cases_drafted' => (int) DB::table('draft_versions')->where('author_type', 'ai')->whereBetween('created_at', [$from, $to])->count(),
        ];
    }

    /**
     * @param  array<string, int|float|null>  $m
     * @param  list<string>  $flags
     */
    private function body(Carbon $from, Carbon $to, array $m, array $flags): string
    {
        $tz = 'Asia/Riyadh';
        $lines = [
            'ملخص صحة الذكاء الاصطناعي في DentLegal',
            sprintf('الفترة: %s إلى %s (توقيت الرياض)', $from->copy()->tz($tz)->format('Y-m-d H:i'), $to->copy()->tz($tz)->format('Y-m-d H:i')),
            '',
            $flags === [] ? 'لا توجد ملاحظات.' : "ملاحظات:\n- ".implode("\n- ", $flags),
            '',
            "إجابات المستشار: {$m['answers']} · اعتذارات: {$m['refusals']}".($m['refusal_rate'] === null ? '' : ' ('.self::pct((float) $m['refusal_rate']).'%)'),
            'صحة الاستشهادات (تحقق آلي): '.($m['citation_validity'] === null ? '—' : self::pct((float) $m['citation_validity']).'%'),
            "مسودات أُنشئت: {$m['cases_drafted']} · طلبات للذكاء الاصطناعي: {$m['runs']} · نموذج بديل: {$m['fallbacks']}",
            sprintf('التكلفة: $%.2f', (float) $m['cost_usd']),
            '',
            'التفاصيل في بوابة الإدارة: «سجل الذكاء الاصطناعي» مع «المشكلات فقط».',
        ];

        return implode("\n", $lines);
    }

    private static function pct(float $v): string
    {
        return rtrim(rtrim(number_format($v * 100, 1), '0'), '.');
    }
}
