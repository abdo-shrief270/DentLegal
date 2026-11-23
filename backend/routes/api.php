<?php

use App\Modules\Cases\Http\Controllers\CaseController;
use App\Modules\Consultations\Http\Controllers\ConsultationController;
use App\Modules\Documents\Http\Controllers\DocumentController;
use App\Modules\Identity\Http\Controllers\MeController;
use App\Modules\Identity\Http\Controllers\OtpController;
use App\Modules\Knowledge\Http\Controllers\ChunkController;
use App\Modules\Pipeline\Http\Controllers\AnalysisController;
use App\Modules\Pipeline\Http\Controllers\DraftController;
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

        Route::get('consultations/suggested-questions', [ConsultationController::class, 'suggestedQuestions'])->name('consultations.suggested');
        Route::get('consultations', [ConsultationController::class, 'index'])->name('consultations.index');
        Route::post('consultations', [ConsultationController::class, 'store'])->name('consultations.store');
        Route::get('consultations/{consultation}', [ConsultationController::class, 'show'])->name('consultations.show');
        Route::post('consultations/{consultation}/messages', [ConsultationController::class, 'ask'])->name('consultations.ask');
        Route::get('consultation-messages/{message}', [ConsultationController::class, 'message'])->name('consultations.message');

        Route::get('cases', [CaseController::class, 'index'])->name('cases.index');
        Route::post('cases', [CaseController::class, 'store'])->name('cases.store');
        Route::get('cases/{case}', [CaseController::class, 'show'])->name('cases.show');
        Route::post('cases/{case}/documents', [DocumentController::class, 'store'])->middleware('throttle:uploads')->name('documents.store');
        Route::delete('cases/{case}/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
        Route::post('cases/{case}/analysis', [AnalysisController::class, 'start'])->name('analysis.start');
        Route::get('cases/{case}/analysis/status', [AnalysisController::class, 'status'])->name('analysis.status');
        Route::get('cases/{case}/analysis', [AnalysisController::class, 'show'])->name('analysis.show');
        Route::get('cases/{case}/draft', [DraftController::class, 'show'])->name('draft.show');
        Route::post('cases/{case}/submit-review', [DraftController::class, 'submitReview'])->name('draft.submit');

        Route::get('knowledge/chunks/{chunk}', [ChunkController::class, 'show'])->whereNumber('chunk')->name('knowledge.chunks.show');
    });
});
