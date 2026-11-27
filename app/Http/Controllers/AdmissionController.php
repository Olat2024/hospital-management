<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Hospital;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdmissionController extends Controller
{
    public function index(Request $request): Response
    {
        $hospital = Hospital::default();
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'admitted');
        if (! in_array($status, ['admitted', 'discharged', 'all'], true)) {
            $status = 'admitted';
        }

        $admissions = Admission::query()
            ->whereHas('patient', fn ($query) => $query->where('hospital_id', $hospital->id))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(fn ($sub) => $sub
                    ->where('reason', 'like', $like)
                    ->orWhereHas('patient', fn ($patient) => $patient
                        ->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('mrn', 'like', $like)));
            })
            ->with([
                'patient:id,mrn,first_name,last_name,phone',
                'admittedBy:id,name',
                'dischargedBy:id,name',
            ])
            ->orderByDesc('admitted_at')
            ->paginate(15)
            ->withQueryString();

        $hospitalAdmissions = Admission::query()
            ->whereHas('patient', fn ($query) => $query->where('hospital_id', $hospital->id));

        return Inertia::render('Admissions/Index', [
            'admissions' => $admissions,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'summary' => [
                'admitted' => (clone $hospitalAdmissions)->where('status', 'admitted')->count(),
                'dischargedToday' => (clone $hospitalAdmissions)
                    ->where('status', 'discharged')
                    ->whereDate('discharged_at', today())
                    ->count(),
            ],
        ]);
    }

    public function store(Request $request, int $patient): RedirectResponse
    {
        $hospital = Hospital::default();
        $timezone = $hospital->timezone ?: config('app.timezone');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'admitted_at' => [
                'bail',
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($timezone): void {
                    if (Carbon::parse($value, $timezone)->isAfter(now($timezone))) {
                        $fail('The admission date and time must not be in the future.');
                    }
                },
            ],
        ]);
        $validated['admitted_at'] = Carbon::parse($validated['admitted_at'], $timezone)
            ->utc()
            ->toDateTimeString();

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
