<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PatientController extends Controller
{
    /**
     * Patient directory with search + pagination.
     */
    public function index(Request $request)
    {
        $hospitalId = Hospital::default()->id;

        $query = Patient::query()->where('hospital_id', $hospitalId);

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('mrn', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $gender = $request->query('gender');
        if (in_array($gender, ['Male', 'Female', 'Other'], true)) {
            $query->where('gender', $gender);
        }

        return Inertia::render('Patients/Index', [
            'patients' => $query->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(12)
                ->withQueryString(),
            'filters' => [
                'search' => $search,
                'gender' => in_array($gender, ['Male', 'Female', 'Other'], true) ? $gender : '',
            ],
            'can' => [
                'create' => $request->user()->can('patients.create'),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Patients/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'dob' => 'required|date|before:today',
            'gender' => 'required|in:Male,Female,Other',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:1000',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'allergies' => 'nullable|string|max:1000',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_number' => 'nullable|string|max:100',
        ]);

        // The schema stores a single address, so fold the location fields into it.
        $validated['address'] = collect([
            $validated['address'],
            $validated['city'],
            $validated['state'],
        ])->filter()->implode(', ');

        unset($validated['city'], $validated['state']);

        $patient = Patient::create($validated + [
            'hospital_id' => Hospital::default()->id,
            'mrn' => self::nextMrn(),
        ]);

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Patient registered successfully.');
    }

    public function show(Request $request, Patient $patient)
    {
        $canViewAppointments = $request->user()->can('appointments.view');
        $canViewMedicalRecords = $request->user()->can('medical-records.view');
        $canViewVitals = $request->user()->can('nursing.view');
        $relations = [];

        if ($canViewAppointments) {
            $relations['appointments'] = fn ($query) => $query->orderByDesc('scheduled_at');
            $relations[] = 'appointments.doctor';
        }

        if ($canViewMedicalRecords) {
            $relations['medicalRecords.doctor'] = fn ($query) => $query->orderByDesc('created_at');
        }

        if ($canViewVitals) {
            $relations['vitalSigns'] = fn ($query) => $query
                ->orderByDesc('recorded_at')
                ->with('recorder:id,name');
        }

        $patient->load($relations);

        return Inertia::render('Patients/Show', [
            'patient' => $patient,
            'can' => [
                'viewAppointments' => $canViewAppointments,
                'viewMedicalRecords' => $canViewMedicalRecords,
                'viewVitals' => $canViewVitals,
                'manageVitals' => $request->user()->can('nursing.manage'),
                'addNote' => $request->user()->can('medical-records.create'),
                'bookAppointment' => $request->user()->can('appointments.create'),
            ],
        ]);
    }

    /**
     * Medical Record Number, e.g. MRN-261023-00001
     *
     * Sequences per day, derived from the highest MRN issued that day so
     * deletions never cause a duplicate (the column is unique).
     */
    protected static function nextMrn(): string
    {
        $prefix = 'MRN-'.now()->format('ymd').'-';

        $last = Patient::where('mrn', 'like', $prefix.'%')
            ->orderByDesc('mrn')
            ->value('mrn');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%05d', $prefix, $next);
    }
}
