<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\HospitalSetup;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HospitalSetupController extends Controller
{
    private const SHARED_FIELDS = [
        'primary_color',
        'secondary_color',
        'name',
        'tagline',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'email',
        'website',
        'social_links',
        'footer_text',
        'timezone',
        'currency',
        'locale',
    ];

    public function __construct(private readonly ImageUploadService $images)
    {
    }

    public function edit(): Response
    {
        $hospital = Hospital::default();
        $setup = HospitalSetup::forHospital($hospital->id);

        return Inertia::render('Settings/HospitalSetup', [
            'setup' => $setup,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $hospital = Hospital::default();
        $setup = HospitalSetup::forHospital($hospital->id);

        $validated = $request->validate([
            // Branding
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:512'],
            'remove_favicon' => ['nullable', 'boolean'],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            // Identity
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],

            // Address
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],

            // Contact
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],

            // Social / Meta
            'social_links' => ['nullable', 'json'],
            'footer_text' => ['nullable', 'string', 'max:1000'],

            // Settings
            'timezone' => ['nullable', 'string', 'max:64', 'timezone'],
            'currency' => ['nullable', 'string', 'size:3'],
            'locale' => ['nullable', 'string', 'max:5'],
        ]);

        $setup->logo = $this->images->syncImage(
            $request,
            'logo',
            'hospitals',
            $setup->logo,
            $request->boolean('remove_logo'),
            'Failed to store the hospital logo.'
        );
        $setup->favicon = $this->images->syncImage(
            $request,
            'favicon',
            'hospitals',
            $setup->favicon,
            $request->boolean('remove_favicon'),
            'Failed to store the hospital favicon.'
        );

        unset(
            $validated['logo'],
            $validated['remove_logo'],
            $validated['favicon'],
            $validated['remove_favicon']
        );

        if (! empty($validated['social_links'])) {
            $decoded = json_decode($validated['social_links'], true);
            $validated['social_links'] = is_array($decoded) ? $decoded : null;
        } else {
            $validated['social_links'] = null;
        }

        DB::transaction(function () use ($setup, $hospital, $validated) {
            $setup->fill($validated);
            $setup->save();

            // Keep the core Hospital record aligned so billing currency and timezones stay in sync.
            $hospital->fill(Arr::only($validated, self::SHARED_FIELDS));
            $hospital->save();
        });

        return redirect()
            ->route('settings.hospital-setup.edit')
            ->with('success', 'Hospital setup saved.');
    }
}
