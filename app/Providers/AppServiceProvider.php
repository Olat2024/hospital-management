<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        // Administrators always pass authorization checks, even for permissions
        // added after the roles were last seeded.
        Gate::before(fn (User $user) => $user->hasAnyRole(['Admin', 'super-admin']) ? true : null);

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
