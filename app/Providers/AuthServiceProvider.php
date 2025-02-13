<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Admin has all permissions
        Gate::before(function (User $user) {
            if ($user->email === 'ernesto@cobos.io') {
                return true;
            }
        });

        // Define role-based permissions
        Gate::define('manage_strategies', function (User $user) {
            return $user->role === User::ROLE_ADMIN;
        });

        Gate::define('manage_trading_pairs', function (User $user) {
            return $user->role === User::ROLE_ADMIN;
        });

        Gate::define('view_logs', function (User $user) {
            return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_TRADER]);
        });

        Gate::define('view_analytics', function (User $user) {
            return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_TRADER, User::ROLE_VIEWER]);
        });
    }
}
