<?php
namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Doctor directory + staff onboarding (Admin only).
 *
 * Every doctor needs a login account, so store() provisions a User with the
 * Doctor role. destroy() deactivates rather than deletes: doctors.user_id is a
 * cascading FK and medical_records.doctor_id cascades too, so a hard delete
 * would silently destroy patient clinical notes.
 */
class DoctorController extends Controller {

    public function index(Request $request) {
        $search = trim((string) $request->query('search', ''));

        $query = Doctor::with('user')->withCount('appointments');

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('specialization', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', $like));
            });
        }

        return Inertia::render('Doctors/Index', [
            'doctors' => $query->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(12)
                ->withQueryString(),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create() {
        return Inertia::render('Doctors/Create');
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'is_active' => 'nullable|boolean',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $doctor = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['first_name'].' '.$validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Not fillable on User, so set it explicitly: doctors must land on
            // the verified side of the `verified` middleware.
            $user->email_verified_at = now();
            $user->save();

            $user->assignRole('Doctor');

            return Doctor::create([
                'user_id' => $user->id,
                'hospital_id' => Hospital::default()->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'specialization' => $validated['specialization'],
                'phone' => $validated['phone'],
                'is_active' => $validated['is_active'] ?? true,
            ]);
        });

        return redirect()->route('doctors.index')
            ->with('success', "Dr. {$doctor->first_name} {$doctor->last_name} added successfully.");
    }

    public function edit(Doctor $doctor) {
        return Inertia::render('Doctors/Edit', [
            'doctor' => $doctor->load('user'),
        ]);
    }

    public function update(Request $request, Doctor $doctor) {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'is_active' => 'nullable|boolean',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($doctor->user_id)],
            'password' => 'nullable|string|min:8',
        ]);

        DB::transaction(function () use ($doctor, $validated) {
            $doctor->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'specialization' => $validated['specialization'],
                'phone' => $validated['phone'],
                'is_active' => $validated['is_active'] ?? $doctor->is_active,
            ]);

            $user = $doctor->user;

            $user->name = $validated['first_name'].' '.$validated['last_name'];
            $user->email = $validated['email'];

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();
        });

        return redirect()->route('doctors.index')
            ->with('success', "Dr. {$validated['last_name']} updated successfully.");
    }

    /**
     * Deactivate (never hard-delete) a doctor to preserve clinical records.
     */
    public function destroy(Request $request, Doctor $doctor) {
        $doctor->update(['is_active' => false]);

        return redirect()->route('doctors.index')
            ->with('success', "Dr. {$doctor->last_name} deactivated. Existing records are preserved.");
    }
}
