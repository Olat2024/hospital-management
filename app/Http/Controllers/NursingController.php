<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\Patient;
use App\Models\VitalSign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class NursingController extends Controller
{
    public function index(Request $request): Response
    {
        $hospital = Hospital::default();
        $search = trim((string) $request->query('search', ''));
        $today = now()->startOfDay();

        $patients = Patient::query()
            ->where('hospital_id', $hospital->id)
            ->with(['latestVitalSign.recorder:id,name'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where('mrn', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            }))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(12)
            ->withQueryString();

        $hospitalPatients = Patient::query()
            ->where('hospital_id', $hospital->id);

        $vitalsToday = VitalSign::query()
            ->whereHas('patient', fn ($query) => $query->where('hospital_id', $hospital->id))
            ->where('recorded_at', '>=', $today);
        $selectedPatientId = $request->integer('patient_id');
        $selectedPatient = $selectedPatientId
            ? Patient::query()
                ->where('hospital_id', $hospital->id)
                ->find($selectedPatientId)
            : null;

        return Inertia::render('Nursing/Index', [
            'patients' => $patients,
            'filters' => ['search' => $search],
            'selectedPatient' => $selectedPatient,
            'summary' => [
                'patientCount' => $hospitalPatients->count(),
                'readingsToday' => (clone $vitalsToday)->count(),
                'patientsMeasuredToday' => (clone $vitalsToday)->distinct('patient_id')->count('patient_id'),
            ],
            'canManage' => $request->user()->can('nursing.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $hospital = Hospital::default();
        $measurementFields = [
            'systolic_blood_pressure',
            'diastolic_blood_pressure',
            'pulse_rate',
            'respiratory_rate',
            'temperature_celsius',
            'oxygen_saturation',
            'weight_kg',
            'height_cm',
            'pain_score',
        ];

        $validator = Validator::make($request->all(), [
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('patients', 'id')->where('hospital_id', $hospital->id),
            ],
            'systolic_blood_pressure' => ['nullable', 'integer', 'between:40,300'],
            'diastolic_blood_pressure' => ['nullable', 'integer', 'between:20,200'],
            'pulse_rate' => ['nullable', 'integer', 'between:20,300'],
            'respiratory_rate' => ['nullable', 'integer', 'between:4,80'],
            'temperature_celsius' => ['nullable', 'numeric', 'between:25,45'],
            'oxygen_saturation' => ['nullable', 'integer', 'between:50,100'],
            'weight_kg' => ['nullable', 'numeric', 'between:0.2,500'],
            'height_cm' => ['nullable', 'numeric', 'between:20,250'],
            'pain_score' => ['nullable', 'integer', 'between:0,10'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validator->after(function ($validator) use ($request, $measurementFields) {
            $hasMeasurement = collect($measurementFields)
                ->contains(fn ($field) => $request->input($field) !== null && $request->input($field) !== '');

            if (! $hasMeasurement) {
                $validator->errors()->add('systolic_blood_pressure', 'Record at least one clinical measurement.');
            }
        });

        $validated = $validator->validate();
        $patient = Patient::query()
            ->where('hospital_id', $hospital->id)
            ->findOrFail($validated['patient_id']);
        unset($validated['patient_id']);

        $patient->vitalSigns()->create($validated + [
            'recorded_by' => $request->user()->id,
            'recorded_at' => now(),
        ]);

        return back()->with('success', 'Patient vital signs recorded successfully.');
    }
}
