<?php

namespace App\Providers;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Http\Controllers\Dashboard\DashboardAuthController;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        // Admins only, signed in to the dashboard with two-factor checked in this session (S9.3).
        Gate::define('viewHorizon', function ($user = null): bool {
            return $user instanceof User
                && $user->status === UserStatus::Active
                && $user->hasRole(Role::Admin->value)
                && $user->two_factor_confirmed_at !== null
                && request()->hasSession()
                && request()->session()->has(DashboardAuthController::PASSED);
        });
    }
}
