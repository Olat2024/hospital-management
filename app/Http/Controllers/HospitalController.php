<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HospitalController extends Controller
{
    public function index()
    {
        $this->authorize('view-hospital');

        $hospitals = Hospital::with(['patients', 'doctors', 'appointments'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Hospitals/Index', [
            'hospitals' => $hospitals,
        ]);
    }

    public function create()
    {
        $this->authorize('create-hospital');

        return Inertia::render('Hospitals/Create');
    }

    public function store(Request $request)
    {
        $this->authorize('create-hospital');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        Hospital::create($validated);

        return redirect()
            ->route('hospitals.index')
            ->with('success', 'Hospital created successfully.');
    }

    public function show(Hospital $hospital)
    {
        $this->authorize('view-hospital');

        return Inertia::render('Hospitals/Show', [
            'hospital' => $hospital->load(['patients', 'doctors', 'appointments']),
        ]);
    }

    public function edit(Hospital $hospital)
    {
        $this->authorize('edit-hospital');

        return Inertia::render('Hospitals/Edit', [
            'hospital' => $hospital,
        ]);
    }

    public function update(Request $request, Hospital $hospital)
    {
        $this->authorize('edit-hospital');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $hospital->update($validated);

        return redirect()
            ->route('hospitals.index')
            ->with('success', 'Hospital updated successfully.');
    }

    public function destroy(Hospital $hospital)
    {
        $this->authorize('delete-hospital');

        $hospital->delete();

        return redirect()
            ->route('hospitals.index')
            ->with('success', 'Hospital deleted successfully.');
    }
}
