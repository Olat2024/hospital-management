<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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

        $grants = fn (User $user, string ...$permissions): bool => collect($permissions)
            ->contains(fn (string $permission) => $user->getAllPermissions()->contains('name', $permission));

        // Gates used by App\Http\Controllers\HospitalController, backed by the
        // permissions managed on the Settings > Roles & Permissions screen.
        Gate::define('view-hospital', fn (User $user) => $grants($user, 'hospital.view', 'hospital.manage'));
        Gate::define('create-hospital', fn (User $user) => $grants($user, 'hospital.manage'));
        Gate::define('edit-hospital', fn (User $user) => $grants($user, 'hospital.manage'));
        Gate::define('delete-hospital', fn (User $user) => $grants($user, 'hospital.manage'));

        // Administrators always pass authorization checks, even for permissions
        // added after the roles were last seeded.
        Gate::before(fn (User $user) => $user->hasRole('Admin') ? true : null);
    }
}
