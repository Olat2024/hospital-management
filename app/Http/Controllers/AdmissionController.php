<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Hospital;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdmissionController extends Controller
{
    public function index(): Response
    {
        $hospital = Hospital::default();

        return Inertia::render('Admissions/Index', [
            'admissions' => Admission::query()
                ->where('status', 'admitted')
                ->whereHas('patient', fn ($query) => $query->where('hospital_id', $hospital->id))
                ->with('patient:id,mrn,first_name,last_name,phone')
                ->orderBy('admitted_at')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request, int $patient): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'admitted_at' => ['required', 'date', 'before_or_equal:now'],
        ]);
        $hospital = Hospital::default();

        DB::transaction(function () use ($patient, $hospital, $validated, $request) {
            $patient = Patient::query()
                ->where('hospital_id', $hospital->id)
                ->lockForUpdate()
                ->findOrFail($patient);

            $alreadyAdmitted = $patient->admissions()
                ->where('status', 'admitted')
                ->exists();

            if ($alreadyAdmitted) {
                throw ValidationException::withMessages([
                    'reason' => 'This patient already has an active admission.',
                ]);
            }

            $patient->admissions()->create([
                'admitted_by' => $request->user()->id,
                'status' => 'admitted',
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'admitted_at' => $validated['admitted_at'],
            ]);
        });

        return back()->with('success', 'Patient admitted successfully.');
    }

    public function discharge(Request $request, int $admission): RedirectResponse
    {
        $validated = $request->validate([
            'discharge_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $hospital = Hospital::default();

        DB::transaction(function () use ($admission, $hospital, $validated, $request) {
            $admission = Admission::query()
                ->where('status', 'admitted')
                ->whereHas('patient', fn ($query) => $query->where('hospital_id', $hospital->id))
                ->lockForUpdate()
                ->findOrFail($admission);

            $admission->update([
                'status' => 'discharged',
                'discharged_by' => $request->user()->id,
                'discharge_notes' => $validated['discharge_notes'] ?? null,
                'discharged_at' => now(),
            ]);
        });

        return back()->with('success', 'Patient discharged successfully.');
    }
}
