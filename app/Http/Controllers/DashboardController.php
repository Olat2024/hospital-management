<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $hospital = Hospital::default();

        // If the logged-in user is a Doctor, fetch their specific schedule
        if ($user->hasRole('Doctor')) {
            $doctor = Doctor::where('user_id', $user->id)->first();

            $appointments = $doctor ? Appointment::with('patient')
                ->where('doctor_id', $doctor->id)
                ->whereDate('scheduled_at', today())
                ->orderBy('scheduled_at')
                ->get() : collect();

            return Inertia::render('Dashboard/Doctor', [
                'doctor' => $doctor,
                'appointments' => $appointments,
                'stats' => [
                    'todayAppointments' => $appointments->count(),
                    'waiting' => $appointments->where('status', 'scheduled')->count(),
                ],
            ]);
        }

        // Default overview for Admins and Receptionists
        $hospitalId = $hospital->id;

        return Inertia::render('Dashboard/Staff', [
            'stats' => [
                'totalPatients' => Patient::where('hospital_id', $hospitalId)->count(),
                'todayAppointments' => Appointment::where('hospital_id', $hospitalId)
                    ->whereDate('scheduled_at', today())->count(),
                'waiting' => Appointment::where('hospital_id', $hospitalId)
                    ->whereDate('scheduled_at', today())
                    ->where('status', 'scheduled')->count(),
                'doctors' => Doctor::where('hospital_id', $hospitalId)->active()->count(),
            ],
            'recentAppointments' => Appointment::with(['patient', 'doctor'])
                ->where('hospital_id', $hospitalId)
                ->whereDate('scheduled_at', today())
                ->orderBy('scheduled_at')
                ->take(5)
                ->get(),
        ]);
    }
}
