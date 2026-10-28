<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class AppointmentController extends Controller {

    /** Statuses an appointment may hold. */
    public const STATUSES = ['scheduled', 'completed', 'cancelled', 'no_show'];

    /**
     * Appointment schedule with date / doctor / status filters.
     */
    public function index(Request $request) {
        $hospitalId = Hospital::default()->id;

        $filters = [
            'date' => (string) $request->query('date', now()->toDateString()),
            'doctor_id' => (int) $request->query('doctor_id', 0),
            'status' => (string) $request->query('status', ''),
            'search' => trim((string) $request->query('search', '')),
        ];

        if (!in_array($filters['status'], self::STATUSES, true)) {
            $filters['status'] = '';
        }

        $base = Appointment::query()
            ->where('hospital_id', $hospitalId)
            ->when($filters['date'] !== '', fn ($q) => $q->whereDate('scheduled_at', $filters['date']))
            ->when($filters['doctor_id'] > 0, fn ($q) => $q->where('doctor_id', $filters['doctor_id']))
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['search'] !== '', function ($q) use ($filters) {
                $like = "%{$filters['search']}%";
                $q->where(function ($q) use ($like) {
                    $q->whereHas('patient', fn ($p) => $p
                        ->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('mrn', 'like', $like));
                });
            });

        // Per-status totals for the active filter (ignoring the status filter itself).
        $summary = $base->clone()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Appointments/Index', [
            'appointments' => (clone $base)
                ->with(['patient', 'doctor'])
                ->orderBy('scheduled_at')
                ->paginate(12)
                ->withQueryString(),
            'doctors' => Doctor::active()->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'specialization']),
            'filters' => $filters,
            'summary' => [
                'total' => (int) $base->count(),
                'scheduled' => (int) ($summary['scheduled'] ?? 0),
                'completed' => (int) ($summary['completed'] ?? 0),
                'cancelled' => (int) ($summary['cancelled'] ?? 0),
                'no_show' => (int) ($summary['no_show'] ?? 0),
            ],
            'can' => [
                'book' => $request->user()->can('appointments.create'),
                'manage' => $request->user()->can('appointments.manage')
                    || $request->user()->can('appointments.create')
                    || $request->user()->hasRole('Doctor'),
            ],
        ]);
    }

    public function create() {
        return Inertia::render('Appointments/Create', [
            // Pass minimal data to the frontend for the dropdowns
            'patients' => Patient::orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'doctors' => Doctor::active()->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'specialization'])
        ]);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|string|max:8',
            'notes' => 'nullable|string|max:2000'
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);

        Appointment::create([
            'hospital_id' => $patient->hospital_id,
            'patient_id' => $patient->id,
            'doctor_id' => $validated['doctor_id'],
            'scheduled_at' => self::combine($validated['appointment_date'], $validated['appointment_time']),
            'status' => 'scheduled',
            'type' => 'opd',
            'reason' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('appointments.index', ['date' => $validated['appointment_date']])
            ->with('success', 'Appointment booked successfully.');
    }

    /**
     * Change an appointment's status, or move it to a new date/time.
     */
    public function update(Request $request, Appointment $appointment) {
        $this->authorizeStatusChange($request);

        $validated = $request->validate([
            'status' => 'nullable|in:'.implode(',', self::STATUSES),
            'scheduled_date' => 'nullable|date',
            'scheduled_time' => 'nullable|string|max:8',
        ]);

        $status = $validated['status'] ?? 'scheduled';

        // A reschedule always returns the appointment to "scheduled".
        if (($validated['scheduled_date'] ?? null) !== null) {
            $status = 'scheduled';
            $appointment->scheduled_at = self::combine(
                $validated['scheduled_date'],
                $validated['scheduled_time'] ?? $appointment->scheduled_at->format('H:i')
            );
        }

        $appointment->status = $status;
        $appointment->save();

        return back()->with('success', 'Appointment updated successfully.');
    }

    /**
     * Booking clerks may manage their own bookings; doctors may update their
     * patients' appointments. Admin passes through Gate::before.
     */
    protected function authorizeStatusChange(Request $request): void {
        $user = $request->user();

        $allowed = $user->can('appointments.manage')
            || $user->can('appointments.create')
            || $user->hasRole('Doctor');

        abort_unless($allowed, 403);
    }

    /**
     * Browsers post either "H:i" or "H:i:s" depending on the time input.
     */
    protected static function combine(string $date, string $time): Carbon {
        return Carbon::parse($date.' '.substr($time, 0, 5));
    }
}
