<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $users = User::query()
            ->with('roles:id,name')
            ->withExists(['paymentsRecorded', 'pharmacyDispensationsRecorded', 'pharmacyStockMovementsRecorded'])
            ->withExists('doctorProfile')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(fn ($query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'like', $like)));
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Staff/Index', [
            'staff' => $users->through(fn (User $user) => [
                ...$user->toArray(),
                'can_manage' => ! $user->hasAnyRole(['Admin', 'Doctor'])
                    && ! $user->doctor_profile_exists
                    && $user->id !== $request->user()->id,
                'can_delete' => ! $user->hasAnyRole(['Admin', 'Doctor'])
                    && ! $user->doctor_profile_exists
                    && $user->id !== $request->user()->id
                    && ! $user->payments_recorded_exists
                    && ! $user->pharmacy_dispensations_recorded_exists
                    && ! $user->pharmacy_stock_movements_recorded_exists,
            ]),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Staff/Create', ['roles' => $this->staffRoles()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')
                    ->where('guard_name', 'web')
                    ->whereNotIn('name', ['Admin', 'Doctor']),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignRole(Role::findById($validated['role_id'], 'web'));

            return $user;
        });

        return redirect()->route('staff.index')
            ->with('success', "{$user->name} was added to staff.");
    }

    public function edit(Request $request, User $staff): Response
    {
        abort_unless($staff->id !== $request->user()->id && $this->isManageable($staff), 404);

        return Inertia::render('Staff/Edit', [
            'staff' => $staff->load('roles:id,name'),
            'roles' => $this->staffRoles(),
        ]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        abort_unless($staff->id !== $request->user()->id && $this->isManageable($staff), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff->id)],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')
                    ->where('guard_name', 'web')
                    ->whereNotIn('name', ['Admin', 'Doctor']),
            ],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($validated, $staff) {
            $staff->name = $validated['name'];
            $staff->email = $validated['email'];
            $staff->is_active = $validated['is_active'];

            if ($validated['password'] ?? false) {
                $staff->password = Hash::make($validated['password']);
            }

            $staff->save();
            $staff->syncRoles([Role::findById($validated['role_id'], 'web')]);
        });

        return redirect()->route('staff.index')
            ->with('success', "{$staff->name}'s staff account was updated.");
    }

    public function destroy(Request $request, User $staff): RedirectResponse
    {
        if ($staff->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'staff' => 'You cannot delete your own account.',
            ]);
        }

        abort_unless($this->isManageable($staff), 404);

        DB::transaction(function () use ($staff) {
            $staff = User::query()->lockForUpdate()->findOrFail($staff->id);
            abort_unless($this->isManageable($staff), 404);

            if (Payment::query()->where('recorded_by', $staff->id)->exists()) {
                throw ValidationException::withMessages([
                    'staff' => 'This account has payment audit history and cannot be deleted.',
                ]);
            }

            if ($staff->pharmacyDispensationsRecorded()->exists()
                || $staff->pharmacyStockMovementsRecorded()->exists()) {
                throw ValidationException::withMessages([
                    'staff' => 'This account has pharmacy audit history and cannot be deleted.',
                ]);
            }

            $staff->delete();
        });

        return redirect()->route('staff.index')
            ->with('success', "{$staff->name}'s account was deleted.");
    }

    private function isManageable(User $staff): bool
    {
        return ! $staff->hasAnyRole(['Admin', 'Doctor'])
            && ! $staff->doctorProfile()->exists();
    }

    private function staffRoles()
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', ['Admin', 'Doctor'])
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
