<?php

namespace App\Providers;

use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewAdmin', function ($user) {
            return $user->hasAnyRole([Roles::ADMIN, Roles::SUPER_ADMIN]);
        });

        Request::macro('currentRole', function () {
            /** @var Request $this */
            return Roles::highestRoleFor($this->user());
        });

        View::composer('*', function ($view) {
            $user = Auth::user();

            $view->with('authUser', $user);
            $view->with('currentRole', Roles::highestRoleFor($user));
        });
    }
}
