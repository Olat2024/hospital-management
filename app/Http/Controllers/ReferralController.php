<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReferralController extends Controller
{
    public function store(Request $request, int $patient): RedirectResponse
    {
        $validated = $request->validate([
            'referred_to' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'referred_at' => ['required', 'date', 'before_or_equal:now'],
        ]);
        $hospital = Hospital::default();
        $doctor = Doctor::query()
            ->where('user_id', $request->user()->id)
            ->where('hospital_id', $hospital->id)
            ->first();

        if ($request->user()->hasRole('Doctor') && ! $doctor) {
            throw ValidationException::withMessages([
                'referred_to' => 'No doctor profile is linked to your account.',
            ]);
        }

        $patient = Patient::query()
            ->where('hospital_id', $hospital->id)
            ->findOrFail($patient);

        $patient->referrals()->create([
            ...$validated,
            'doctor_id' => $doctor?->id,
            'recorded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Referral recorded in the patient history.');
    }
}
