<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Single-hospital setup screen (branding, contact details, logo).
 */
class HospitalSettingsController extends Controller
{
    /**
     * Display the hospital setup form.
     */
    public function edit(): Response
    {
        return Inertia::render('Settings/Hospital', [
            'setup' => Hospital::default(),
        ]);
    }

    /**
     * Update the hospital setup.
     */
    public function update(Request $request): RedirectResponse
    {
        $hospital = Hospital::default();

        $validated = $request->validate([
            // Branding
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'favicon' => ['nullable', 'image', 'mimes:png,ico,svg', 'max:1024'],
            'remove_favicon' => ['nullable', 'boolean'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],

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
            'is_active' => ['nullable', 'boolean'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'currency' => ['nullable', 'string', 'size:3'],
            'locale' => ['nullable', 'string', 'max:5'],
        ]);

        // ---- Logo ----
        if ($request->hasFile('logo')) {
            $logo = $request->file('logo')->store('hospitals', config('filesystems.uploads_disk', 'public'));
            if ($logo === false) {
                throw new RuntimeException('Failed to store the hospital logo.');
            }

            if ($hospital->logo) {
                Storage::disk(config('filesystems.uploads_disk', 'public'))->delete($hospital->logo);
            }
            $hospital->logo = $logo;
        } elseif (! empty($validated['remove_logo']) && $hospital->logo) {
            Storage::disk(config('filesystems.uploads_disk', 'public'))->delete($hospital->logo);
            $hospital->logo = null;
        }

        // ---- Favicon ----
        if ($request->hasFile('favicon')) {
            $favicon = $request->file('favicon')->store('hospitals', config('filesystems.uploads_disk', 'public'));
            if ($favicon === false) {
                throw new RuntimeException('Failed to store the hospital favicon.');
            }

            if ($hospital->favicon) {
                Storage::disk(config('filesystems.uploads_disk', 'public'))->delete($hospital->favicon);
            }
            $hospital->favicon = $favicon;
        } elseif (! empty($validated['remove_favicon']) && $hospital->favicon) {
            Storage::disk(config('filesystems.uploads_disk', 'public'))->delete($hospital->favicon);
            $hospital->favicon = null;
        }

        // ---- Files/flags must not go through fill() ----
        unset(
            $validated['logo'],
            $validated['remove_logo'],
            $validated['favicon'],
            $validated['remove_favicon']
        );

        // ---- social_links may arrive as JSON string ----
        if (! empty($validated['social_links'])) {
            $decoded = json_decode($validated['social_links'], true);
            $validated['social_links'] = is_array($decoded) ? $decoded : null;
        } else {
            $validated['social_links'] = null;
        }

        // ---- Defaults ----
        $validated['is_active'] = $request->boolean('is_active');

        $hospital->fill($validated);
        $hospital->save();

        return redirect()
            ->route('settings.hospital')
            ->with('success', 'Hospital setup saved.');
    }
}
