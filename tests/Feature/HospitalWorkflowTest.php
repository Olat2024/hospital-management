<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HospitalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Hospital $hospital;
    protected User $admin;
    protected User $doctorUser;
    protected User $receptionist;
    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->hospital = Hospital::create(['name' => 'Test Hospital']);

        $this->admin = User::factory()->create(['email_verified_at' => now()]);
        $this->admin->assignRole('Admin');

        $this->doctorUser = User::factory()->create(['email_verified_at' => now()]);
        $this->doctorUser->assignRole('Doctor');
        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'hospital_id' => $this->hospital->id,
            'first_name' => 'Jane',
            'last_name' => 'Okafor',
            'specialization' => 'General Medicine',
            'phone' => '08011112222',
        ]);

        $this->receptionist = User::factory()->create(['email_verified_at' => now()]);
        $this->receptionist->assignRole('Receptionist');
    }

    protected function makePatient(array $overrides = []): Patient
    {
        return Patient::create(array_merge([
            'hospital_id' => $this->hospital->id,
            'mrn' => 'MRN-'.now()->format('ymd').'-00001',
            'first_name' => 'Adebayo',
            'last_name' => 'Ogunleye',
            'dob' => '1990-04-12',
            'gender' => 'Male',
            'phone' => '08033334444',
            'address' => '1 Hospital Road, Ijebu Ode, Ogun State',
        ], $overrides));
    }

    // ---------------------------------------------------------------
    // Patient directory
    // ---------------------------------------------------------------

    public function test_admin_can_list_patients_with_search(): void
    {
        $this->makePatient();
        $this->makePatient([
            'mrn' => 'MRN-'.now()->format('ymd').'-00002',
            'first_name' => 'Chinwe',
            'last_name' => 'Okonkwo',
        ]);

        $this->actingAs($this->admin)
            ->get(route('patients.index', ['search' => 'Chinwe']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Patients/Index')
                ->has('patients.data', 1)
                ->where('patients.data.0.last_name', 'Okonkwo')
                ->where('filters.search', 'Chinwe')
            );
    }

    public function test_doctor_can_view_patient_chart_including_clinical_notes(): void
    {
        $patient = $this->makePatient();

        $this->doctor->medicalRecords()->create([
            'patient_id' => $patient->id,
            'diagnosis' => 'Malaria',
            'prescription' => 'Artemether 20mg',
        ]);

        $this->actingAs($this->doctorUser)
            ->get(route('patients.show', $patient))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Patients/Show')
                ->has('patient.medical_records', 1)
                ->where('can.addNote', true)
            );
    }

    public function test_receptionist_cannot_see_the_add_note_button(): void
    {
        $patient = $this->makePatient();

        $this->actingAs($this->receptionist)
            ->get(route('patients.show', $patient))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('can.addNote', false));
    }

    public function test_patient_registration_generates_sequential_mrn(): void
    {
        $this->makePatient();

        $this->actingAs($this->receptionist)->post(route('patients.store'), [
            'first_name' => 'Tunde',
            'last_name' => 'Bakare',
            'dob' => '1985-09-01',
            'gender' => 'Male',
            'phone' => '08055556666',
            'address' => '20 Market Street',
            'city' => 'Ijebu Ode',
            'state' => 'Ogun State',
            'allergies' => 'Penicillin',
            'insurance_provider' => 'Hygeia',
            'insurance_number' => 'HYG-991',
        ])->assertRedirect();

        $created = Patient::where('last_name', 'Bakare')->firstOrFail();

        $this->assertSame('Penicillin', $created->allergies);
        $this->assertSame('HYG-991', $created->insurance_number);
        $this->assertStringContainsString('Ijebu Ode', $created->address);
        $this->assertMatchesRegularExpression('/^MRN-\d{6}-00002$/', $created->mrn);
    }

    // ---------------------------------------------------------------
    // Appointments
    // ---------------------------------------------------------------

    public function test_staff_can_view_schedule_and_summary(): void
    {
        $patient = $this->makePatient();
        Appointment::create([
            'hospital_id' => $this->hospital->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'scheduled_at' => now()->setTime(9, 30),
            'status' => 'scheduled',
            'type' => 'opd',
        ]);

        $this->actingAs($this->receptionist)
            ->get(route('appointments.index', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Appointments/Index')
                ->has('appointments.data', 1)
                ->where('summary.scheduled', 1)
                ->where('summary.total', 1)
            );
    }

    public function test_receptionist_can_cancel_an_appointment(): void
    {
        $patient = $this->makePatient();
        $appointment = Appointment::create([
            'hospital_id' => $this->hospital->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'scheduled_at' => now()->addDay()->setTime(10, 0),
            'status' => 'scheduled',
            'type' => 'opd',
        ]);

        $this->actingAs($this->receptionist)
            ->put(route('appointments.update', $appointment), ['status' => 'cancelled'])
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $appointment->fresh()->status);
    }

    public function test_doctor_can_complete_and_reschedule_an_appointment(): void
    {
        $patient = $this->makePatient();
        $appointment = Appointment::create([
            'hospital_id' => $this->hospital->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'scheduled_at' => now()->setTime(11, 0),
            'status' => 'scheduled',
            'type' => 'opd',
        ]);

        $this->actingAs($this->doctorUser)
            ->put(route('appointments.update', $appointment), ['status' => 'completed'])
            ->assertSessionHas('success');

        $this->assertSame('completed', $appointment->fresh()->status);

        // Rescheduling reopens the appointment at the new time.
        $this->actingAs($this->doctorUser)
            ->put(route('appointments.update', $appointment), [
                'scheduled_date' => now()->addDays(3)->toDateString(),
                'scheduled_time' => '14:45',
            ])
            ->assertSessionHas('success');

        $fresh = $appointment->fresh();
        $this->assertSame('scheduled', $fresh->status);
        $this->assertSame('14:45', $fresh->scheduled_at->format('H:i'));
    }

    public function test_invalid_status_is_rejected(): void
    {
        $patient = $this->makePatient();
        $appointment = Appointment::create([
            'hospital_id' => $this->hospital->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'scheduled_at' => now(),
            'status' => 'scheduled',
            'type' => 'opd',
        ]);

        $this->actingAs($this->admin)
            ->put(route('appointments.update', $appointment), ['status' => 'exploded'])
            ->assertSessionHasErrors('status');
    }

    // ---------------------------------------------------------------
    // Clinical notes
    // ---------------------------------------------------------------

    public function test_doctor_can_add_a_clinical_note_to_a_patient_chart(): void
    {
        $patient = $this->makePatient();

        $this->actingAs($this->doctorUser)->post(route('medical-records.store'), [
            'patient_id' => $patient->id,
            'diagnosis' => 'Typhoid fever',
            'prescription' => 'Ciprofloxacin 500mg',
            'notes' => 'Follow up in 7 days',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('medical_records', [
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'diagnosis' => 'Typhoid fever',
        ]);
    }

    public function test_receptionist_cannot_add_clinical_notes(): void
    {
        $patient = $this->makePatient();

        $this->actingAs($this->receptionist)->post(route('medical-records.store'), [
            'patient_id' => $patient->id,
            'diagnosis' => 'Not allowed',
        ])->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Doctor management
    // ---------------------------------------------------------------

    public function test_admin_can_onboard_a_doctor_with_login_credentials(): void
    {
        $this->actingAs($this->admin)->post(route('doctors.store'), [
            'first_name' => 'Amina',
            'last_name' => 'Yusuf',
            'specialization' => 'Pediatrics',
            'phone' => '08077778888',
            'email' => 'amina.yusuf@hospital.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('doctors.index'));

        $doctor = Doctor::where('last_name', 'Yusuf')->firstOrFail();

        $this->assertSame($this->hospital->id, $doctor->hospital_id);
        $this->assertTrue($doctor->is_active);

        $user = User::where('email', 'amina.yusuf@hospital.com')->firstOrFail();
        $this->assertTrue($user->hasRole('Doctor'));
        $this->assertTrue($user->email_verified_at !== null);
    }

    public function test_receptionist_cannot_manage_doctors(): void
    {
        $this->actingAs($this->receptionist)
            ->get(route('doctors.index'))
            ->assertForbidden();
    }

    public function test_deactivating_a_doctor_preserves_clinical_records(): void
    {
        $patient = $this->makePatient();
        $this->doctor->medicalRecords()->create([
            'patient_id' => $patient->id,
            'diagnosis' => 'Keep me',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('doctors.destroy', $this->doctor))
            ->assertRedirect(route('doctors.index'));

        $this->assertFalse($this->doctor->fresh()->is_active);
        // The whole point: deactivation must not cascade-delete patient notes.
        $this->assertDatabaseHas('medical_records', ['diagnosis' => 'Keep me']);
        $this->assertDatabaseHas('users', ['id' => $this->doctorUser->id]);
    }
}
