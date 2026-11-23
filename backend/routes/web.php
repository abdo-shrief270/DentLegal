<?php

use App\Modules\Identity\Http\Controllers\Dashboard\DashboardAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
| Dashboard sign-in (PROJECT1_PLAN.md §4.2, S5.7): cookie session + CSRF (GET /sanctum/csrf-cookie first).
| Lawyer and admin API routes use auth:sanctum + `two_factor` (routes/api.php).
*/
Route::post('login', [DashboardAuthController::class, 'login'])->middleware('throttle:dashboard-login')->name('dashboard.login');
Route::post('two-factor-challenge', [DashboardAuthController::class, 'challenge'])->middleware('throttle:dashboard-2fa')->name('dashboard.two-factor');
Route::middleware('throttle:invitations')->group(function (): void {
    Route::get('invitations/{token}', [DashboardAuthController::class, 'invitation'])->where('token', '[A-Za-z0-9]{32,80}')->name('dashboard.invitation');
    Route::post('invitations/{token}/accept', [DashboardAuthController::class, 'acceptInvitation'])->where('token', '[A-Za-z0-9]{32,80}')->name('dashboard.invitation.accept');
});

Route::middleware('auth:web')->group(function (): void {
    Route::get('user', [DashboardAuthController::class, 'show'])->name('dashboard.user');
    Route::post('user/two-factor-authentication', [DashboardAuthController::class, 'enable'])->middleware('throttle:dashboard-2fa')->name('dashboard.two-factor.enable');
    Route::post('user/confirmed-two-factor-authentication', [DashboardAuthController::class, 'confirm'])->middleware('throttle:dashboard-2fa')->name('dashboard.two-factor.confirm');
    Route::post('logout', [DashboardAuthController::class, 'logout'])->name('dashboard.logout');
});
