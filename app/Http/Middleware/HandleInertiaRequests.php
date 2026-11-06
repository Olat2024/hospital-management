<?php

namespace App\Http\Middleware;

use App\Models\Hospital;
use App\Models\HospitalSetup;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Log;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Determine which props are always shared with the front-end.
     *
     * @return array<string, mixed>
     */
        /**
     * Determine which props are always shared with the front-end.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $activeHospital = null;
        $setup = null;

        // Wrap all DB queries in a try-catch to prevent 500 errors 
        // if the database is slow, maxed out on connections, or missing tables.
        try {
            $activeHospital = Hospital::query()->where('is_active', true)->first();
            
            if ($activeHospital) {
                $setup = HospitalSetup::query()->where('hospital_id', $activeHospital->id)->first();
            }
        } catch (\Throwable $e) {
            // Log the error for debugging, but don't crash the page
            Log::warning('Inertia Share DB Query Failed: ' . $e->getMessage());
        }

        $hospital = $user ? $activeHospital : null;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                // Spatie roles for role-based navigation.
                'roles' => $user ? $user->getRoleNames() : [],
                'permissions' => $user ? $user->getAllPermissions()->pluck('name') : [],
            ],
            // Hospital branding shown in the application shell.
            'hospital' => $hospital ? $hospital->only(['id', 'name']) : null,
            // Logo from HospitalSetup (new rich setup) or fallback to Hospital (legacy)
            'hospital_logo_url' => $setup?->logo_url ?? $hospital?->logo_url,
            'hospital_favicon_url' => $setup?->favicon_url ?? $activeHospital?->favicon_url,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
