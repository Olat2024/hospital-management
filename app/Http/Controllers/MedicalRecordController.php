<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
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

        // Automatically fetch the doctor profile linked to the current logged-in user
        $doctor = Doctor::where('user_id', $request->user()->id)
            ->where('hospital_id', $hospital->id)
            ->first();

        if (! $doctor) {
            return back()->withErrors(['error' => 'No doctor profile found for this user account.']);
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
