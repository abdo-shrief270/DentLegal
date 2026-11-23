<?php

use App\Modules\Cases\Http\Controllers\CaseController;
use App\Modules\Identity\Http\Controllers\MeController;
use App\Modules\Identity\Http\Controllers\OtpController;
use App\Modules\Knowledge\Http\Controllers\ChunkController;
use Illuminate\Support\Facades\Route;

/*
| /api/v1 — PROJECT1_PLAN.md §4.2. Mobile routes require X-App-Version; dentist routes require a token with the
| `dentist` ability and the dentist role. Lawyer and admin routes (cookie session + 2FA) are added with S5.7.
*/

Route::prefix('v1')->middleware('app.version')->group(function (): void {
    Route::post('auth/otp/request', [OtpController::class, 'request'])->name('auth.otp.request');
    Route::post('auth/otp/verify', [OtpController::class, 'verify'])->name('auth.otp.verify');

    Route::middleware(['auth:sanctum', 'abilities:dentist', 'role:dentist', 'throttle:api'])->group(function (): void {
        Route::post('auth/logout', [MeController::class, 'logout'])->name('auth.logout');

        Route::get('me', [MeController::class, 'show'])->name('me.show');
        Route::put('me/profile', [MeController::class, 'updateProfile'])->name('me.profile');
        Route::post('me/consents', [MeController::class, 'consents'])->name('me.consents');
        Route::put('me/devices/current', [MeController::class, 'updateDevice'])->name('me.device');
        Route::delete('me', [MeController::class, 'destroy'])->name('me.destroy');

        Route::get('cases', [CaseController::class, 'index'])->name('cases.index');
        Route::post('cases', [CaseController::class, 'store'])->name('cases.store');
        Route::get('cases/{case}', [CaseController::class, 'show'])->name('cases.show');

        Route::get('knowledge/chunks/{chunk}', [ChunkController::class, 'show'])->whereNumber('chunk')->name('knowledge.chunks.show');
    });
});
