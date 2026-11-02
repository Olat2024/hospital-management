<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MedicalRecordController extends Controller
{
    public function store(Request $request)
    {
        $hospital = Hospital::default();
        $validated = $request->validate([
            'patient_id' => ['required', Rule::exists('patients', 'id')->where('hospital_id', $hospital->id)],
            'diagnosis' => 'required|string',
            'prescription' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $doctor = $request->user()->doctorProfile;

        if (! $doctor) {
            return back()->withErrors([
                'error' => 'This Doctor login is not linked to a doctor profile. Ask an administrator to link it from Doctors → Add Doctor.',
            ]);
        }

        $validated['doctor_id'] = $doctor->id;

        Patient::where('hospital_id', $hospital->id)
            ->findOrFail($validated['patient_id'])
            ->medicalRecords()
            ->create([
                'doctor_id' => $validated['doctor_id'],
                'diagnosis' => $validated['diagnosis'],
                'prescription' => $validated['prescription'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

        return back()->with('success', 'Medical record added successfully.');
    }
}
