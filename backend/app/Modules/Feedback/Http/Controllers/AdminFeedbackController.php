<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Http\Controllers;

use App\Modules\Audit\AuditLogger;
use App\Modules\Feedback\Models\Feedback;
use App\Modules\Feedback\Models\Rating;
use App\Modules\Feedback\Support\RatingTargets;
use App\Modules\Identity\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** «التقييمات» and «الملاحظات» (S7.3): filters, CSV export (`?format=csv`), feedback status. */
final class AdminFeedbackController
{
    private const LIMIT = 1000;

    public function ratings(Request $request): JsonResponse|StreamedResponse
    {
        $filters = $request->validate([
            'target_type' => ['nullable', 'in:consultation_message,analysis,draft_version'],
            'useful' => ['nullable', 'in:0,1,true,false'],
            'from' => ['nullable', 'date'], 'to' => ['nullable', 'date'],
            'format' => ['nullable', 'in:json,csv'],
        ]);
        $ratings = Rating::query()->with('user:id,name')
            ->when($filters['target_type'] ?? null, fn ($q, $t) => $q->where('target_type', $t))
            ->when(isset($filters['useful']), fn ($q) => $q->where('useful', filter_var($filters['useful'], FILTER_VALIDATE_BOOLEAN)))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->where('created_at', '<', now()->parse($d)->addDay()))
            ->orderByDesc('created_at')->limit(self::LIMIT)->get();
        $described = RatingTargets::describe($ratings);
        $rows = $ratings->map(fn (Rating $r): array => [
            'id' => $r->id, 'created_at' => $r->created_at->toIso8601ZuluString(), 'dentist_name' => $r->user->name,
            'target_type' => $r->target_type, 'target_label' => $described[$r->id]['label'], 'target_excerpt' => $described[$r->id]['excerpt'],
            'useful' => $r->useful, 'comment' => $r->comment,
        ])->values()->all();

        return ($filters['format'] ?? 'json') === 'csv'
            ? self::csv('ratings', ['created_at', 'dentist_name', 'target_type', 'target_label', 'useful', 'comment'], $rows)
            : response()->json(['data' => $rows]);
    }

    public function feedback(Request $request): JsonResponse|StreamedResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:new,seen,done'],
            'category' => ['nullable', 'in:bug,suggestion,other'],
            'format' => ['nullable', 'in:json,csv'],
        ]);
        $rows = Feedback::query()->with('user:id,name')
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->where('category', $c))
            ->orderByDesc('created_at')->limit(self::LIMIT)->get()
            ->map(fn (Feedback $f): array => [
                'id' => $f->id, 'created_at' => $f->created_at->toIso8601ZuluString(), 'dentist_name' => $f->user?->name,
                'category' => $f->category, 'message' => $f->message, 'screen' => $f->screen, 'app_version' => $f->app_version,
                'platform' => $f->platform, 'status' => $f->status,
            ])->values()->all();

        return ($filters['format'] ?? 'json') === 'csv'
            ? self::csv('feedback', ['created_at', 'dentist_name', 'category', 'message', 'screen', 'app_version', 'platform', 'status'], $rows)
            : response()->json(['data' => $rows]);
    }

    public function setStatus(Request $request, string $id, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:new,seen,done']]);
        $feedback = Feedback::query()->findOrFail($id);
        $feedback->update(['status' => $data['status']]);
        /** @var User $admin */
        $admin = $request->user();
        $audit->record('feedback.status_changed', $admin, $feedback, ['status' => $data['status']]);

        return response()->json(['data' => ['id' => $feedback->id, 'status' => $feedback->status]]);
    }

    /**
     * UTF-8 with a BOM so Excel opens the Arabic text correctly; cells that start with = + - @ are quoted against
     * formula injection.
     *
     * @param  list<string>  $columns
     * @param  list<array<string, mixed>>  $rows
     */
    private static function csv(string $name, array $columns, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($columns, $rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $columns, escape: '');
            foreach ($rows as $row) {
                fputcsv($out, array_map(function (string $c) use ($row): string {
                    $value = $row[$c] ?? '';
                    $value = is_bool($value) ? ($value ? 'yes' : 'no') : (string) $value;

                    return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
                }, $columns), escape: '');
            }
            fclose($out);
        }, $name.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
