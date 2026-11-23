<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Api\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Mobile requests must send `X-App-Version` (e.g. "1.0.0 (12)"); builds older than the minimum get 426 (§4.1). */
final class RequireAppVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('X-App-Version', '');
        $version = preg_match('/^(\d+\.\d+\.\d+)/', $header, $m) === 1 ? $m[1] : null;

        if ($version === null || version_compare($version, (string) config('dentlegal.min_app_version'), '<')) {
            throw new ApiException(426, 'app_update_required', 'يتوفر إصدار أحدث من التطبيق. حدّث التطبيق للمتابعة.', ['min_version' => config('dentlegal.min_app_version')]);
        }

        return $next($request);
    }
}
