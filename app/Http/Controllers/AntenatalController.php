<?php

namespace App\Http\Controllers;

use App\Models\AntenatalCard;
use App\Models\Hospital;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AntenatalController extends Controller
{
    public function index(): Response
    {
        $hospital = Hospital::default();

        return Inertia::render('Antenatal/Index', [
            'cards' => AntenatalCard::query()
                ->where('hospital_id', $hospital->id)
                ->with('patient:id,mrn,first_name,last_name,phone')
                ->withCount('visits')
                ->latest('booking_date')
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        $hospital = Hospital::default();

        return Inertia::render('Antenatal/Create', [
            'patients' => Patient::query()
                ->where('hospital_id', $hospital->id)
                ->where('gender', 'Female')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'mrn', 'first_name', 'last_name', 'dob', 'phone', 'address']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $hospital = Hospital::default();
        $validated = $request->validate([
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('patients', 'id')
                    ->where('hospital_id', $hospital->id)
                    ->where('gender', 'Female'),
            ],
            'booking_date' => ['nullable', 'date'],
            'pregnancy_duration_at_booking' => ['nullable', 'string', 'max:100'],
            'age_at_booking' => ['nullable', 'integer', 'between:10,80'],
            'age_at_marriage' => ['nullable', 'integer', 'between:10,80'],
            'last_menstrual_period' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:last_menstrual_period'],
            'tribe' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'special_points' => ['nullable', 'string', 'max:2000'],
            'previous_pregnancies_total' => ['nullable', 'integer', 'min:0', 'max:50'],
            'living_children' => ['nullable', 'integer', 'min:0', 'max:50'],
            'is_ems_patient' => ['nullable', 'boolean'],
            'medical_history' => ['nullable', 'array'],
            'medical_history.heart_disease' => ['nullable', 'boolean'],
            'medical_history.chest_disease' => ['nullable', 'boolean'],
            'medical_history.kidney_disease' => ['nullable', 'boolean'],
            'medical_history.diabetes' => ['nullable', 'boolean'],
            'medical_history.twinning' => ['nullable', 'boolean'],
            'medical_history.operations' => ['nullable', 'string', 'max:1000'],
            'medical_history.allergies' => ['nullable', 'string', 'max:1000'],
            'medical_history.other' => ['nullable', 'string', 'max:1000'],
            'previous_pregnancies' => ['nullable', 'array', 'max:20'],
            'previous_pregnancies.*.date_of_birth' => ['nullable', 'date'],
            'previous_pregnancies.*.duration' => ['nullable', 'string', 'max:100'],
            'previous_pregnancies.*.birth_weight' => ['nullable', 'numeric', 'between:0,10'],
            'previous_pregnancies.*.pregnancy_complications' => ['nullable', 'string', 'max:1000'],
            'previous_pregnancies.*.labour_complications' => ['nullable', 'string', 'max:1000'],
            'previous_pregnancies.*.puerperium' => ['nullable', 'string', 'max:1000'],
            'previous_pregnancies.*.baby_age_at_death' => ['nullable', 'string', 'max:100'],
            'previous_pregnancies.*.baby_cause_of_death' => ['nullable', 'string', 'max:1000'],
            'visits' => ['nullable', 'array', 'max:30'],
            'visits.*.visit_date' => ['nullable', 'date'],
            'visits.*.fundal_height_cm' => ['nullable', 'numeric', 'between:0,100'],
            'visits.*.presentation_position' => ['nullable', 'string', 'max:255'],
            'visits.*.presenting_part_relation' => ['nullable', 'string', 'max:255'],
            'visits.*.foetal_heart' => ['nullable', 'string', 'max:100'],
            'visits.*.urine' => ['nullable', 'string', 'max:100'],
            'visits.*.blood_pressure' => ['nullable', 'string', 'max:30'],
            'visits.*.weight_kg' => ['nullable', 'numeric', 'between:0,500'],
            'visits.*.pcv' => ['nullable', 'numeric', 'between:0,100'],
            'visits.*.oedema' => ['nullable', 'string', 'max:100'],
            'visits.*.remarks' => ['nullable', 'string', 'max:2000'],
            'visits.*.examiner_initials' => ['nullable', 'string', 'max:20'],
            'visits.*.return_date' => ['nullable', 'date'],
        ]);

        foreach ($validated['visits'] ?? [] as $index => $visit) {
            if (collect($visit)->except('visit_date')->contains(fn ($value) => filled($value))
                && empty($visit['visit_date'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "visits.{$index}.visit_date" => 'Enter a date for this visit before saving its clinical details.',
                ]);
            }
        }

        $patient = Patient::query()
            ->where('hospital_id', $hospital->id)
            ->findOrFail($validated['patient_id']);

        $card = DB::transaction(function () use ($validated, $request, $hospital, $patient) {
            $card = AntenatalCard::create([
                'hospital_id' => $hospital->id,
                'patient_id' => $patient->id,
                'created_by' => $request->user()->id,
                'booking_date' => $validated['booking_date'] ?? null,
                'pregnancy_duration_at_booking' => $validated['pregnancy_duration_at_booking'] ?? null,
                'age_at_booking' => $validated['age_at_booking'] ?? null,
                'age_at_marriage' => $validated['age_at_marriage'] ?? null,
                'last_menstrual_period' => $validated['last_menstrual_period'] ?? null,
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'tribe' => $validated['tribe'] ?? null,
                'occupation' => $validated['occupation'] ?? null,
                'special_points' => $validated['special_points'] ?? null,
                'previous_medical_history' => $validated['medical_history'] ?? [],
                'previous_pregnancies' => collect($validated['previous_pregnancies'] ?? [])
                    ->filter(fn ($pregnancy) => collect($pregnancy)->contains(fn ($value) => filled($value)))
                    ->values()
                    ->all(),
                'previous_pregnancies_total' => $validated['previous_pregnancies_total'] ?? null,
                'living_children' => $validated['living_children'] ?? null,
            ]);

            foreach ($validated['visits'] ?? [] as $visit) {
                if (! empty($visit['visit_date'])) {
                    $card->visits()->create($visit);
                }
            }

            $patient->update([
                'pregnancy_status' => 'Pregnant',
                'is_ems_patient' => $validated['is_ems_patient'] ?? false,
                'pregnancy_notes' => $validated['special_points'] ?? null,
            ]);

            return $card;
        });

        return redirect()->route('antenatal.show', $card)
            ->with('success', 'ANC card registered successfully.');
    }

    public function show(Request $request, AntenatalCard $antenatalCard): Response
    {
        abort_unless($antenatalCard->hospital_id === Hospital::default()->id, 404);
        $antenatalCard->load(['patient:id,mrn,first_name,last_name,dob,phone,address', 'visits']);

        return Inertia::render('Antenatal/Show', [
            'card' => $antenatalCard,
            'canManage' => $request->user()->can('antenatal.manage'),
        ]);
    }

    public function storeVisit(Request $request, AntenatalCard $antenatalCard): RedirectResponse
    {
        abort_unless($antenatalCard->hospital_id === Hospital::default()->id, 404);
        $validated = $request->validate($this->visitRules());

        $antenatalCard->visits()->create($validated);

        return back()->with('success', 'ANC visit recorded successfully.');
    }

    private function visitRules(): array
    {
        return [
            'visit_date' => ['required', 'date'],
            'fundal_height_cm' => ['nullable', 'numeric', 'between:0,100'],
            'presentation_position' => ['nullable', 'string', 'max:255'],
            'presenting_part_relation' => ['nullable', 'string', 'max:255'],
            'foetal_heart' => ['nullable', 'string', 'max:100'],
            'urine' => ['nullable', 'string', 'max:100'],
            'blood_pressure' => ['nullable', 'string', 'max:30'],
            'weight_kg' => ['nullable', 'numeric', 'between:0,500'],
            'pcv' => ['nullable', 'numeric', 'between:0,100'],
            'oedema' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'examiner_initials' => ['nullable', 'string', 'max:20'],
            'return_date' => ['nullable', 'date'],
        ];
    }
}
