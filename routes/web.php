<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\HospitalSettingsController;
use App\Http\Controllers\HospitalSetupController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\NursingController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StaffController;
use App\Models\Hospital;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $hospital = Hospital::query()->where('is_active', true)->first();
    $setup = $hospital?->setup;

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'hospitalName' => $setup?->name ?: ($hospital?->name ?: 'CarePoint Hospital'),
        'tagline' => $setup?->tagline ?: ($hospital?->tagline ?: 'Compassionate care. Connected teams.'),
        'logoUrl' => $setup?->logo_url ?? $hospital?->logo_url,
    ]);
});

// ---------------------------------------------------------
// Profile (auth only, no email verification required)
// ---------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ---------------------------------------------------------
// Core application (auth + verified email)
// ---------------------------------------------------------
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard (every signed-in staff member)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ACL: patient registration (Receptionist / Admin)
    Route::middleware(['permission:patients.create'])->group(function () {
        Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
        Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
    });

    // ACL: appointment booking (Receptionist / Admin)
    Route::middleware(['permission:appointments.create'])->group(function () {
        Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
        Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    });

    // ACL: viewing a patient chart (Doctor / Receptionist / Admin)
    Route::middleware(['permission:patients.view'])->group(function () {
        // Explicit URI registered before {patient} so it never binds.
        Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
        Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
    });

    // ACL: viewing the appointment schedule (Doctor / Receptionist / Admin)
    Route::get('/appointments', [AppointmentController::class, 'index'])
        ->middleware('permission:appointments.view')
        ->name('appointments.index');

    // Status changes / reschedules. Auth + verified only: the controller allows
    // booking clerks (appointments.create) and Doctors, not just managers.
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])
        ->name('appointments.update');

    // ACL: writing clinical notes (Doctor / Admin)
    Route::post('/medical-records', [MedicalRecordController::class, 'store'])
        ->middleware('permission:medical-records.create')
        ->name('medical-records.store');

    Route::get('/nursing', [NursingController::class, 'index'])
        ->middleware('permission:nursing.view')
        ->name('nursing.index');

    Route::post('/nursing/vitals', [NursingController::class, 'store'])
        ->middleware('permission:nursing.manage')
        ->name('nursing.vitals.store');

    Route::get('/pharmacy', [PharmacyController::class, 'index'])
        ->middleware('permission:pharmacy.view')
        ->name('pharmacy.index');

    Route::middleware('permission:pharmacy.manage')->prefix('pharmacy')->name('pharmacy.')->group(function () {
        Route::post('/medicines', [PharmacyController::class, 'storeMedicine'])->name('medicines.store');
        Route::post('/medicines/{medicine}/stock', [PharmacyController::class, 'receiveStock'])->name('stock.store');
        Route::post('/medicines/{medicine}/dispense', [PharmacyController::class, 'dispenseStock'])->name('stock.dispense');
        Route::post('/prescriptions/{record}/dispense', [PharmacyController::class, 'dispense'])->name('dispense');
    });

    // ACL: invoice records (Receptionist / Admin / Doctor view)
    Route::get('/billing', [InvoiceController::class, 'index'])
        ->middleware('permission:billing.view')
        ->name('billing.index');

    Route::get('/billing/analytics', [InvoiceController::class, 'analytics'])
        ->middleware('permission:billing.view')
        ->name('billing.analytics');

    Route::get('/billing/create', [InvoiceController::class, 'create'])
        ->middleware('permission:billing.create')
        ->name('billing.create');

    Route::post('/billing', [InvoiceController::class, 'store'])
        ->middleware('permission:billing.create')
        ->name('billing.store');

    Route::get('/billing/{invoice}', [InvoiceController::class, 'show'])
        ->middleware('permission:billing.view')
        ->name('billing.show');

    Route::post('/billing/{invoice}/payments', [InvoiceController::class, 'recordPayment'])
        ->middleware('permission:billing.manage')
        ->name('billing.payments.store');

    Route::post('/billing/{invoice}/void', [InvoiceController::class, 'void'])
        ->middleware('permission:billing.manage')
        ->name('billing.void');

    // ACL: doctor directory & staff onboarding (Admin)
    Route::middleware(['permission:doctors.manage'])->prefix('doctors')->group(function () {
        Route::get('/', [DoctorController::class, 'index'])->name('doctors.index');
        Route::get('/create', [DoctorController::class, 'create'])->name('doctors.create');
        Route::post('/', [DoctorController::class, 'store'])->name('doctors.store');
        Route::get('/{doctor}/edit', [DoctorController::class, 'edit'])->name('doctors.edit');
        Route::put('/{doctor}', [DoctorController::class, 'update'])->name('doctors.update');
        Route::delete('/{doctor}', [DoctorController::class, 'destroy'])->name('doctors.destroy');
    });

    // ACL: staff accounts (Admin)
    Route::middleware('permission:users.manage')->prefix('staff')->name('staff.')->group(function () {
        Route::get('/', [StaffController::class, 'index'])->name('index');
        Route::get('/create', [StaffController::class, 'create'])->name('create');
        Route::post('/', [StaffController::class, 'store'])->name('store');
        Route::get('/{staff}/edit', [StaffController::class, 'edit'])->name('edit');
        Route::put('/{staff}', [StaffController::class, 'update'])->name('update');
        Route::delete('/{staff}', [StaffController::class, 'destroy'])->name('destroy');
    });
});

// ---------------------------------------------------------
// Administration: hospital setup + roles & permissions
// ---------------------------------------------------------
Route::prefix('settings')
    ->name('settings.')
    ->middleware(['auth', 'verified'])
    ->group(function () {

        // ---- Legacy single-hospital setup (kept for backward compatibility) ----
        Route::get('/hospital', [HospitalSettingsController::class, 'edit'])
            ->middleware('permission:hospital.manage')
            ->name('hospital');                       // → settings.hospital

        Route::put('/hospital', [HospitalSettingsController::class, 'update'])
            ->middleware('permission:hospital.manage')
            ->name('hospital.update');                // → settings.hospital.update

        // ---- Rich hospital setup (branding, social, colors, etc.) ----
        Route::get('/hospital-setup', [HospitalSetupController::class, 'edit'])
            ->middleware('permission:hospital.manage')
            ->name('hospital-setup.edit');            // → settings.hospital-setup.edit   ✅ FIXED

        Route::put('/hospital-setup', [HospitalSetupController::class, 'update'])
            ->middleware('permission:hospital.manage')
            ->name('hospital-setup.update');          // → settings.hospital-setup.update

        // ---- Roles & permissions ----
        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('permission:roles.manage')
            ->name('roles');                          // → settings.roles

        Route::post('/roles', [RoleController::class, 'store'])
            ->middleware('permission:roles.manage')
            ->name('roles.store');                    // → settings.roles.store

        Route::put('/roles/{role}', [RoleController::class, 'update'])
            ->middleware('permission:roles.manage')
            ->name('roles.update');                   // → settings.roles.update

        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission:roles.manage')
            ->name('roles.destroy');                  // → settings.roles.destroy

        Route::post('/permissions', [RoleController::class, 'storePermission'])
            ->middleware('permission:roles.manage')
            ->name('permissions.store');              // → settings.permissions.store
    });

require __DIR__.'/auth.php';
