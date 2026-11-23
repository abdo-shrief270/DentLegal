<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Http\Controllers\Dashboard\DashboardAuthController;
use App\Modules\Identity\Models\User;
use App\Support\Api\ApiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lawyer and admin routes (PROJECT1_PLAN.md S5.7): a dashboard cookie session only (no API tokens), an active
 * account, two-factor enabled and checked in this session.
 */
final class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        // Signed in through the dashboard session (the web guard), never through a mobile API token.
        if (! $user instanceof User || ! Auth::guard('web')->check() || Auth::guard('web')->id() !== $user->id) {
            throw ApiException::forbidden('session_required', 'هذه الصفحة متاحة من لوحة التحكم فقط.');
        }
        if ($user->status !== UserStatus::Active) {
            throw ApiException::forbidden('account_disabled', 'الحساب موقوف.');
        }
        if ($user->two_factor_confirmed_at === null || ! $request->session()->has(DashboardAuthController::PASSED)) {
            throw ApiException::forbidden('two_factor_required', 'يلزم إكمال التحقق الثنائي.');
        }

        return $next($request);
    }
}
