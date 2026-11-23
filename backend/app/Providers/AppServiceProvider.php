<?php

namespace App\Providers;

use App\Modules\Ai\AiService;
use App\Modules\Ai\HttpAiService;
use App\Modules\Cases\Models\DentalCase;
use App\Modules\Cases\Policies\CasePolicy;
use App\Modules\Documents\Models\Document;
use App\Modules\Identity\Sms\LogSmsGateway;
use App\Modules\Identity\Sms\SmsGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiService::class, HttpAiService::class);
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
        Route::model('document', Document::class);

        // Authenticated API: 120 requests / minute per token (PROJECT1_PLAN.md §4.7).
        // Uploads: 60 files / hour per dentist (§4.7).
        RateLimiter::for('uploads', fn (Request $request) => Limit::perHour(60)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->currentAccessToken()?->getKey() ?? $request->ip()));
    }
}
