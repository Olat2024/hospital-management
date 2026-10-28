<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\HospitalTestSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PatientRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Routes are guarded by `permission:` middleware, so the ACL matrix
        // must exist in the in-memory database.
        $this->seed(HospitalTestSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_receptionist_can_render_the_registration_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Receptionist');

        $this->actingAs($user)
            ->get(route('patients.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Patients/Create'));
    }

    public function test_patient_can_be_registered(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Receptionist');

        $response = $this->actingAs($user)->post(route('patients.store'), [
            'first_name' => 'Janet',
            'last_name' => 'Smith',
            'dob' => '1990-05-05',
            'gender' => 'Female',
            'blood_group' => 'O-',
            'phone' => '08099998888',
            'email' => 'janet.smith@example.com',
            'address' => '5 Market Road',
            'city' => 'Abeokuta',
            'state' => 'Ogun State',
        ]);

        $patient = Patient::firstOrFail();

        // Registration lands the clerk straight on the new patient's chart.
        $response->assertRedirect(route('patients.show', $patient));

        $this->assertSame('5 Market Road, Abeokuta, Ogun State', $patient->address);
        $this->assertSame('Female', $patient->gender);
        $this->assertNotNull($patient->hospital_id, 'A patient must belong to a hospital.');
        $this->assertNotNull($patient->mrn, 'A patient must be given an MRN.');
        $this->assertSame('May 5, 1990', $patient->dob_display);
    }

    public function test_dob_and_gender_are_required(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Receptionist');

        $response = $this->actingAs($user)
            ->from(route('patients.create'))
            ->post(route('patients.store'), [
                'first_name' => 'Janet',
                'last_name' => 'Smith',
                'phone' => '08099998888',
                'address' => '5 Market Road',
                'city' => 'Abeokuta',
                'state' => 'Ogun State',
            ]);

        $response->assertSessionHasErrors(['dob', 'gender']);
        $this->assertSame(0, Patient::count());
    }

    public function test_doctor_cannot_register_patients(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Doctor');

        $this->actingAs($user)->get(route('patients.create'))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('patients.create'))->assertRedirect(route('login'));
    }
}
