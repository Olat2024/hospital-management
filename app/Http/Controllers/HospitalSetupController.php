<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\HospitalSetup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HospitalSetupController extends Controller
{
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
        $setup = HospitalSetup::forHospital(Hospital::default()->id);

        $validated = $request->validate([
            // Branding
            'logo'            => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'remove_logo'     => ['nullable', 'boolean'],
            'favicon'         => ['nullable', 'image', 'mimes:png,ico,svg', 'max:512'],
            'remove_favicon'  => ['nullable', 'boolean'],
            'primary_color'   => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            // Identity
            'name'    => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],

            // Address
            'address'     => ['nullable', 'string', 'max:1000'],
            'city'        => ['nullable', 'string', 'max:255'],
            'state'       => ['nullable', 'string', 'max:255'],
            'country'     => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],

            // Contact
            'phone'   => ['nullable', 'string', 'max:50'],
            'email'   => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],

            // Social / Meta
            'social_links' => ['nullable', 'json'],
            'footer_text'  => ['nullable', 'string', 'max:1000'],

            // Settings
            'timezone' => ['nullable', 'string', 'max:64'],
            'currency' => ['nullable', 'string', 'size:3'],
            'locale'   => ['nullable', 'string', 'max:5'],
        ]);

        // ---- Logo ----
        if ($request->hasFile('logo')) {
            if ($setup->logo) {
                Storage::disk('public')->delete($setup->logo);
            }
            $setup->logo = $request->file('logo')->store('hospitals', 'public');
        } elseif (! empty($validated['remove_logo']) && $setup->logo) {
            Storage::disk('public')->delete($setup->logo);
            $setup->logo = null;
        }

        // ---- Favicon ----
        if ($request->hasFile('favicon')) {
            if ($setup->favicon) {
                Storage::disk('public')->delete($setup->favicon);
            }
            $setup->favicon = $request->file('favicon')->store('hospitals', 'public');
        } elseif (! empty($validated['remove_favicon']) && $setup->favicon) {
            Storage::disk('public')->delete($setup->favicon);
            $setup->favicon = null;
        }

        unset(
            $validated['logo'],
            $validated['remove_logo'],
            $validated['favicon'],
            $validated['remove_favicon']
        );

        // social_links is validated as JSON string
        if (! empty($validated['social_links'])) {
            $decoded = json_decode($validated['social_links'], true);
            $validated['social_links'] = is_array($decoded) ? $decoded : null;
        } else {
            $validated['social_links'] = null;
        }

        $setup->fill($validated);
        $setup->save();

        return redirect()
            ->route('settings.hospital-setup.edit')
            ->with('success', 'Hospital setup saved.');
    }
}