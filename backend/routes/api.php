<?php

use App\Modules\Admin\Http\Controllers\AdminKnowledgeController;
use App\Modules\Admin\Http\Controllers\AdminUserController;
use App\Modules\Cases\Http\Controllers\CaseController;
use App\Modules\Consultations\Http\Controllers\ConsultationController;
use App\Modules\Documents\Http\Controllers\DocumentController;
use App\Modules\Feedback\Http\Controllers\AdminFeedbackController;
use App\Modules\Feedback\Http\Controllers\AdminStatsController;
use App\Modules\Feedback\Http\Controllers\DentistFeedbackController;
use App\Modules\Identity\Http\Controllers\MeController;
use App\Modules\Identity\Http\Controllers\OtpController;
use App\Modules\Knowledge\Http\Controllers\ChunkController;
use App\Modules\Pipeline\Http\Controllers\AnalysisController;
use App\Modules\Pipeline\Http\Controllers\DraftController;
use App\Modules\Review\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
| /api/v1 — PROJECT1_PLAN.md §4.2. Mobile routes require X-App-Version; dentist routes require a token with the
| `dentist` ability and the dentist role. Lawyer and admin routes use the dashboard cookie session + two-factor.
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

        Route::post('ratings', [DentistFeedbackController::class, 'rate'])->name('ratings.store');
        Route::post('feedback', [DentistFeedbackController::class, 'feedback'])->middleware('throttle:uploads')->name('feedback.store');

        Route::get('knowledge/chunks/{chunk}', [ChunkController::class, 'show'])->whereNumber('chunk')->name('knowledge.chunks.show');
    });
});

// Lawyer: dashboard cookie session (no X-App-Version) with two-factor checked in this session.
Route::prefix('v1/lawyer')->middleware(['auth:sanctum', 'two_factor', 'role:lawyer', 'throttle:dashboard'])->group(function (): void {
    Route::get('reviews', [ReviewController::class, 'index'])->name('lawyer.reviews.index');
    Route::get('reviews/counts', [ReviewController::class, 'counts'])->name('lawyer.reviews.counts');
    Route::get('reviews/{draft}', [ReviewController::class, 'show'])->name('lawyer.reviews.show');
    Route::post('reviews/{draft}/claim', [ReviewController::class, 'claim'])->name('lawyer.reviews.claim');
    Route::post('reviews/{draft}/versions', [ReviewController::class, 'saveVersion'])->name('lawyer.reviews.versions');
    Route::post('reviews/{draft}/notes', [ReviewController::class, 'addNote'])->name('lawyer.reviews.notes');
    Route::post('reviews/{draft}/approve', [ReviewController::class, 'approve'])->name('lawyer.reviews.approve');
});

// Admin: dashboard cookie session with two-factor checked in this session.
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'two_factor', 'role:admin', 'throttle:dashboard'])->group(function (): void {
    Route::get('dentists', [AdminUserController::class, 'dentists'])->name('admin.dentists.index');
    Route::post('dentists', [AdminUserController::class, 'inviteDentist'])->name('admin.dentists.invite');
    Route::get('lawyers', [AdminUserController::class, 'lawyers'])->name('admin.lawyers.index');
    Route::post('lawyers', [AdminUserController::class, 'inviteLawyer'])->name('admin.lawyers.invite');
    Route::post('users/{id}/resend-invite', [AdminUserController::class, 'resendInvite'])->whereUuid('id')->name('admin.users.resend');
    Route::post('users/{user}/activate', [AdminUserController::class, 'activate'])->whereUuid('user')->name('admin.users.activate');
    Route::post('users/{user}/deactivate', [AdminUserController::class, 'deactivate'])->whereUuid('user')->name('admin.users.deactivate');

    Route::get('ratings', [AdminFeedbackController::class, 'ratings'])->name('admin.ratings');
    Route::get('feedback', [AdminFeedbackController::class, 'feedback'])->name('admin.feedback');
    Route::patch('feedback/{id}', [AdminFeedbackController::class, 'setStatus'])->whereUuid('id')->name('admin.feedback.status');
    Route::get('stats/overview', [AdminStatsController::class, 'overview'])->name('admin.stats');

    Route::get('knowledge/sources', [AdminKnowledgeController::class, 'sources'])->name('admin.knowledge.sources');
    Route::post('knowledge/sources/{source}/versions', [AdminKnowledgeController::class, 'upload'])->where('source', 'new|[0-9a-fA-F-]{36}')->name('admin.knowledge.upload');
    Route::get('knowledge/versions/{version}', [AdminKnowledgeController::class, 'version'])->whereUuid('version')->name('admin.knowledge.version');
    Route::get('knowledge/versions/{version}/chunks', [AdminKnowledgeController::class, 'chunks'])->whereUuid('version')->name('admin.knowledge.chunks');
    Route::post('knowledge/versions/{version}/publish', [AdminKnowledgeController::class, 'publish'])->whereUuid('version')->name('admin.knowledge.publish');
    Route::post('knowledge/versions/{version}/archive', [AdminKnowledgeController::class, 'archive'])->whereUuid('version')->name('admin.knowledge.archive');
    Route::post('knowledge/versions/{version}/reindex', [AdminKnowledgeController::class, 'reindex'])->whereUuid('version')->name('admin.knowledge.reindex');
});
