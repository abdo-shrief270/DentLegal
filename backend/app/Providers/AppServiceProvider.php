<?php

namespace App\Providers;

use App\Modules\Ai\AiService;
use App\Modules\Ai\HttpAiService;
use App\Modules\Cases\Models\DentalCase;
use App\Modules\Cases\Policies\CasePolicy;
use App\Modules\Citations\Models\Citation;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Consultations\Models\ConsultationMessage;
use App\Modules\Documents\Models\Document;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Sms\LogSmsGateway;
use App\Modules\Identity\Sms\SmsGateway;
use App\Modules\Knowledge\Models\KnowledgeSourceVersion;
use App\Modules\Notifications\Listeners\NotifyDentistOfApproval;
use App\Modules\Notifications\Push\FcmPushGateway;
use App\Modules\Notifications\Push\LogPushGateway;
use App\Modules\Notifications\Push\PushGateway;
use App\Modules\Pipeline\Models\Draft;
use App\Modules\Review\Events\DraftApproved;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiService::class, HttpAiService::class);
        $this->app->singleton(PushGateway::class, fn () => config('dentlegal.push.driver') === 'fcm'
            ? FcmPushGateway::fromFile((string) config('dentlegal.push.fcm_credentials'))
            : new LogPushGateway);
        $this->app->singleton(SmsGateway::class, fn () => match (config('dentlegal.sms.driver')) {
            'log' => new LogSmsGateway,
            default => throw new \RuntimeException('Unknown SMS driver: '.config('dentlegal.sms.driver')),
        });
    }

    public function boot(): void
    {
        // Fail loudly in development on lazy loading, missing attributes and silently dropped mass-assignment.
        Model::shouldBeStrict(! $this->app->isProduction());

        Gate::policy(DentalCase::class, CasePolicy::class);
        Route::model('case', DentalCase::class);
        Event::listen(DraftApproved::class, NotifyDentistOfApproval::class);

        Route::model('document', Document::class);
        Route::model('consultation', Consultation::class);
        Route::model('message', ConsultationMessage::class);
        Route::model('draft', Draft::class);
        Route::model('citation', Citation::class);
        Route::model('user', User::class);
        Route::model('version', KnowledgeSourceVersion::class);
        Route::pattern('draft', '[0-9a-fA-F-]{36}');
        Route::pattern('consultation', '[0-9a-fA-F-]{36}');
        Route::pattern('message', '[0-9a-fA-F-]{36}');

        // Authenticated API: 120 requests / minute per token (PROJECT1_PLAN.md §4.7).
        // Uploads: 60 files / hour per dentist (§4.7).
        // §4.7: 5 / min per email + IP for the password step; 5 / min per pending or signed-in user + IP for codes.
        RateLimiter::for('dashboard-login', fn (Request $request) => Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('dashboard-2fa', fn (Request $request) => Limit::perMinute(5)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->session()->get('login.pending_id', '')).'|'.$request->ip()));
        RateLimiter::for('invitations', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        // Route-level ceiling on the OTP endpoints; the per-phone and per-IP rules of §4.7 live in the actions.
        RateLimiter::for('otp', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('dashboard', fn (Request $request) => Limit::perMinute(120)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('uploads', fn (Request $request) => Limit::perHour(60)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->currentAccessToken()?->getKey() ?? $request->ip()));
    }
}
