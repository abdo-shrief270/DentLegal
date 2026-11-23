<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Http\Controllers;

use App\Modules\Feedback\Support\UsageStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminStatsController
{
    public function overview(Request $request, UsageStats $stats): JsonResponse
    {
        $range = $request->query('range') === '30d' ? '30d' : '7d';

        return response()->json(['data' => $stats->overview($range)]);
    }
}
