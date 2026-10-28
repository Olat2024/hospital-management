<?php

namespace App\Http\Middleware;

use App\Models\Hospital;
use App\Models\HospitalSetup;
use Illuminate\Http\Request;
use Inertia\Middleware;

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
    public function share(Request $request): array
    {
        $user = $request->user();
        $activeHospital = Hospital::query()->where('is_active', true)->first();

        // Never firstOrFail here: this middleware runs on every authenticated
        // request, so a fresh install with no hospital row must not 500.
        $hospital = $user ? $activeHospital : null;

        // Do not create a setup row during public requests just to resolve branding.
        $setup = $activeHospital
            ? HospitalSetup::query()->where('hospital_id', $activeHospital->id)->first()
            : null;

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
