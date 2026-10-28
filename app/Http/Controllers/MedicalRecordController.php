<?php
namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use App\Models\Doctor;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller {
    public function store(Request $request) {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'diagnosis' => 'required|string',
            'prescription' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Automatically fetch the doctor profile linked to the current logged-in user
        $doctor = Doctor::where('user_id', $request->user()->id)->first();
        
        if (!$doctor) {
            return back()->withErrors(['error' => 'No doctor profile found for this user account.']);
        }

        $validated['doctor_id'] = $doctor->id;

        MedicalRecord::create($validated);

        return back()->with('success', 'Medical record added successfully.');
    }
}