<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\HospitalTestSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $receptionist;

    protected Patient $patient;

    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        // Routes are guarded by `permission:` middleware, so the ACL matrix
        // must exist in the in-memory database.
        $this->seed(HospitalTestSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->receptionist = User::factory()->create();
        $this->receptionist->assignRole('Receptionist');

        $hospital = Hospital::default();

        $this->patient = Patient::create([
            'hospital_id' => $hospital->id,
            'mrn' => 'MRN-TEST-00001',
            'first_name' => 'Adebayo',
            'last_name' => 'Ogunleye',
            'dob' => '1988-04-12',
            'gender' => 'Male',
            'phone' => '08031234567',
            'address' => '14 Oba Avenue, Ijebu Ode, Ogun State',
        ]);

        $doctorUser = User::factory()->create();
        $doctorUser->assignRole('Doctor');

        $this->doctor = Doctor::create([
            'user_id' => $doctorUser->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'specialization' => 'General Medicine',
            'phone' => '08012345678',
        ]);
    }

    public function test_receptionist_can_render_the_booking_form(): void
    {
        $this->actingAs($this->receptionist)
            ->get(route('appointments.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Appointments/Create')
                ->has('patients')
                ->has('doctors'));
    }

    public function test_appointment_is_stored_as_a_single_datetime(): void
    {
        $date = now()->addDays(3)->format('Y-m-d');

        $response = $this->actingAs($this->receptionist)->post(route('appointments.store'), [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $date,
            'appointment_time' => '14:00',
            'notes' => 'Persistent cough for two weeks',
        ]);

        $response->assertRedirect(route('appointments.index', ['date' => $date]));

        $appointment = Appointment::firstOrFail();

        $this->assertSame($date.' 14:00:00', $appointment->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertSame('2:00 PM', $appointment->scheduled_time);
        $this->assertSame($this->patient->hospital_id, $appointment->hospital_id,
            'The appointment inherits the hospital of the patient it was booked for.');
        $this->assertSame('scheduled', $appointment->status);
        $this->assertSame('opd', $appointment->type);
        $this->assertSame('Persistent cough for two weeks', $appointment->reason);
    }

    public function test_appointment_date_cannot_be_in_the_past(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->from(route('appointments.create'))
            ->post(route('appointments.store'), [
                'patient_id' => $this->patient->id,
                'doctor_id' => $this->doctor->id,
                'appointment_date' => now()->subDay()->format('Y-m-d'),
                'appointment_time' => '14:00',
            ]);

        $response->assertSessionHasErrors('appointment_date');
        $this->assertSame(0, Appointment::count());
    }

    public function test_time_with_seconds_is_normalised(): void
    {
        // Some browsers post "H:i:s" from <input type="time">.
        $date = now()->addDay()->format('Y-m-d');

        $this->actingAs($this->receptionist)->post(route('appointments.store'), [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $date,
            'appointment_time' => '09:30:15',
        ]);

        $this->assertSame(
            $date.' 09:30:00',
            Appointment::firstOrFail()->scheduled_at->format('Y-m-d H:i:s')
        );
    }

    public function test_doctor_dashboard_renders_doctor_view(): void
    {
        $doctorUser = $this->doctor->user;

        $this->actingAs($doctorUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard/Doctor')
                ->where('auth.user.name', $doctorUser->name)
                ->where('doctor.id', $this->doctor->id)
                ->has('appointments'));
    }

    public function test_doctor_cannot_book_appointments(): void
    {
        $doctorUser = $this->doctor->user;

        $this->actingAs($doctorUser)
            ->get(route('appointments.create'))
            ->assertForbidden();
    }
}
