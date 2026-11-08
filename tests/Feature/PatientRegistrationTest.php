<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\FamilyCard;
use App\Models\Hospital;
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
        $this->withoutVite();

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

    public function test_receptionist_can_open_individual_patient_registration(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Receptionist');
        $familyCard = FamilyCard::create([
            'hospital_id' => Hospital::default()->id,
            'card_number' => 'FC-TEST-OPEN',
            'family_name' => 'Smith Family',
        ]);

        $this->actingAs($user)
            ->get(route('patients.create.individual', ['family_card_id' => $familyCard->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Patients/IndividualCreate')
                ->has('selectedFamilyCard')
                ->where('selectedFamilyCard.family_name', 'Smith Family'));
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
        $response->assertSessionHas('success', 'Patient registered successfully.');

        $this->assertSame('5 Market Road, Abeokuta, Ogun State', $patient->address);
        $this->assertSame('Female', $patient->gender);
        $this->assertNotNull($patient->hospital_id, 'A patient must belong to a hospital.');
        $this->assertNotNull($patient->mrn, 'A patient must be given an MRN.');
        $this->assertSame('May 5, 1990', $patient->dob_display);
    }

    public function test_individual_patient_can_be_linked_to_a_separately_created_family_card(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Receptionist');
        $familyCard = FamilyCard::create([
            'hospital_id' => Hospital::default()->id,
            'card_number' => 'FC-TEST-0001',
            'family_name' => 'Smith',
        ]);

        $this->actingAs($user)->post(route('patients.store'), [
            'first_name' => 'Janet',
            'last_name' => 'Smith',
            'dob' => '1990-05-05',
            'gender' => 'Female',
            'phone' => '08099998888',
            'address' => '5 Market Road',
            'city' => 'Abeokuta',
            'state' => 'Ogun State',
            'family_card_id' => $familyCard->id,
            'family_relationship' => 'Daughter',
        ]);

        $patient = Patient::firstOrFail();
        $this->assertSame($familyCard->id, $patient->family_card_id);
        $this->assertSame('Daughter', $patient->family_relationship);
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

    public function test_admin_can_edit_patient_details(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $patient = Patient::create([
            'hospital_id' => Hospital::default()->id,
            'mrn' => 'MRN-EDIT-0001',
            'first_name' => 'Janet',
            'last_name' => 'Smith',
            'dob' => '1990-05-05',
            'gender' => 'Female',
            'phone' => '08099998888',
            'address' => 'Old address',
        ]);

        $this->actingAs($admin)
            ->get(route('patients.edit', $patient))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Patients/Edit'));

        $this->actingAs($admin)
            ->put(route('patients.update', $patient), [
                'first_name' => 'Janet',
                'last_name' => 'Adeyemi',
                'dob' => '1990-05-05',
                'gender' => 'Female',
                'phone' => '08011112222',
                'address' => 'Corrected address',
                'family_card_id' => null,
                'family_relationship' => null,
                'pregnancy_status' => 'Not Pregnant',
                'is_ems_patient' => false,
                'pregnancy_notes' => null,
            ])
            ->assertRedirect(route('patients.show', $patient))
            ->assertSessionHas('success', 'Patient record updated successfully.');

        $this->assertSame('Adeyemi', $patient->fresh()->last_name);
        $this->assertSame('Corrected address', $patient->fresh()->address);
    }

    public function test_admin_can_archive_and_restore_patient_without_deleting_it(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $patient = Patient::create([
            'hospital_id' => Hospital::default()->id,
            'mrn' => 'MRN-ARCHIVE-0001',
            'first_name' => 'Janet',
            'last_name' => 'Smith',
            'dob' => '1990-05-05',
            'gender' => 'Female',
            'phone' => '08099998888',
            'address' => '5 Market Road',
        ]);
        $patient->vitalSigns()->create([
            'recorded_at' => now(),
            'notes' => 'History that must be preserved.',
        ]);

        $this->actingAs($admin)
            ->delete(route('patients.destroy', $patient))
            ->assertRedirect(route('patients.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'mrn' => 'MRN-ARCHIVE-0001']);

        $this->actingAs($admin)
            ->get(route('patients.index', ['include_archived' => 1]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Patients/Index')
                ->where('patients.data.0.deleted_at', fn ($value) => filled($value)));

        $this->actingAs($admin)
            ->post(route('patients.restore', $patient->id))
            ->assertRedirect(route('patients.index', ['include_archived' => 1]))
            ->assertSessionHas('success');

        $this->assertNotSoftDeleted('patients', ['id' => $patient->id]);
    }

    public function test_admin_permanently_deletes_a_patient_with_no_linked_history(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $patient = Patient::create([
            'hospital_id' => Hospital::default()->id,
            'mrn' => 'MRN-EMPTY-0001',
            'first_name' => 'Empty',
            'last_name' => 'Record',
            'dob' => '1990-05-05',
            'gender' => 'Female',
            'phone' => '08099998888',
            'address' => '5 Market Road',
        ]);

        $this->actingAs($admin)
            ->delete(route('patients.destroy', $patient))
            ->assertRedirect(route('patients.index'))
            ->assertSessionHas('success', 'Empty patient record permanently deleted.');

        $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
    }

    public function test_non_admin_cannot_edit_or_archive_patient(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole('Receptionist');
        $patient = Patient::create([
            'hospital_id' => Hospital::default()->id,
            'mrn' => 'MRN-NOMANAGE-0001',
            'first_name' => 'Janet',
            'last_name' => 'Smith',
            'dob' => '1990-05-05',
            'gender' => 'Female',
            'phone' => '08099998888',
            'address' => '5 Market Road',
        ]);

        $this->actingAs($receptionist)
            ->get(route('patients.edit', $patient))
            ->assertForbidden();

        $this->actingAs($receptionist)
            ->delete(route('patients.destroy', $patient))
            ->assertForbidden();

        $this->assertNotSoftDeleted('patients', ['id' => $patient->id]);
    }
}
