<?php

use App\Http\Middleware\RequireAppVersion;
use App\Http\Middleware\RequireTwoFactor;
use App\Http\Middleware\SecurityHeaders;
use App\Modules\Admin\Console\InviteAdmin;
use App\Modules\Citations\Console\BackfillCitations;
use App\Modules\Compliance\Console\PurgeExpiredData;
use App\Modules\Compliance\Console\SecurityCheck;
use App\Modules\Feedback\Console\AiHealthDigest;
use App\Modules\Feedback\Console\RollUpUsageStats;
use App\Support\Api\ErrorRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum SPA mode for the dashboard (portal.<domain>): cookie session + CSRF on API routes from stateful domains.
        $middleware->statefulApi();
        $middleware->append(SecurityHeaders::class);
        // Behind Caddy (§8): trust X-Forwarded-* only from the proxies listed in TRUSTED_PROXIES (comma-separated
        // IPs/CIDRs, e.g. the Docker network), so request()->ip() and isSecure() see the real client. Empty = none.
        $proxies = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))));
        if ($proxies !== []) {
            $middleware->trustProxies(at: $proxies);
        }
        $middleware->alias([
            'two_factor' => RequireTwoFactor::class,
            'app.version' => RequireAppVersion::class,
            'abilities' => CheckAbilities::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withCommands([
        RollUpUsageStats::class,
        PurgeExpiredData::class,
        SecurityCheck::class,
        BackfillCitations::class,
        InviteAdmin::class,
        AiHealthDigest::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        ErrorRenderer::register($exceptions);
    })->create();
