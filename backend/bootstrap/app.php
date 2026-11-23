<?php

use App\Http\Middleware\RequireAppVersion;
use App\Http\Middleware\RequireTwoFactor;
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
        $middleware->alias([
            'two_factor' => RequireTwoFactor::class,
            'app.version' => RequireAppVersion::class,
            'abilities' => CheckAbilities::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        ErrorRenderer::register($exceptions);
    })->create();
