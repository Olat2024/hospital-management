<?php

namespace Tests\Feature;

use App\Models\AntenatalCard;
use App\Models\AntenatalVisit;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\HospitalTestSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AntenatalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->seed(HospitalTestSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_nurse_can_view_anc_registration_form(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole('Nurse');

        $this->actingAs($nurse)
            ->get(route('antenatal.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Antenatal/Create'));
    }

    public function test_anc_card_and_initial_visit_are_saved_for_a_hospital_patient(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole('Nurse');
        $patient = Patient::create([
            'hospital_id' => Hospital::default()->id,
            'mrn' => 'MRN-ANC-001',
            'first_name' => 'Ada',
            'last_name' => 'Patient',
            'gender' => 'Female',
            'phone' => '08012345678',
        ]);

        $response = $this->actingAs($nurse)->post(route('antenatal.store'), [
            'patient_id' => $patient->id,
            'booking_date' => '2026-11-08',
            'pregnancy_duration_at_booking' => '12 weeks',
            'age_at_booking' => 28,
            'last_menstrual_period' => '2026-08-16',
            'expected_delivery_date' => '2027-05-23',
            'previous_pregnancies_total' => 1,
            'living_children' => 1,
            'medical_history' => [
                'heart_disease' => false,
                'diabetes' => true,
                'operations' => 'Appendectomy',
            ],
            'previous_pregnancies' => [[
                'date_of_birth' => '2023-02-01',
                'duration' => '39 weeks',
                'birth_weight' => 3.2,
                'pregnancy_complications' => '',
                'labour_complications' => '',
                'puerperium' => '',
                'baby_age_at_death' => '',
                'baby_cause_of_death' => '',
            ]],
            'visits' => [[
                'visit_date' => '2026-11-08',
                'fundal_height_cm' => 12,
                'presentation_position' => 'Longitudinal',
                'blood_pressure' => '120/80',
                'weight_kg' => 64.5,
                'pcv' => 34,
                'examiner_initials' => 'AB',
            ]],
        ]);

        $card = AntenatalCard::firstOrFail();
        $response->assertRedirect(route('antenatal.show', $card));
        $this->assertSame($patient->id, $card->patient_id);
        $this->assertSame(1, $card->previous_pregnancies_total);
        $this->assertTrue($card->previous_medical_history['diabetes']);
        $this->assertSame('Pregnant', $patient->fresh()->pregnancy_status);
        $this->assertDatabaseCount('antenatal_visits', 1);
        $this->assertSame('120/80', AntenatalVisit::firstOrFail()->blood_pressure);
    }

    public function test_anc_card_cannot_be_created_for_a_patient_at_another_hospital(): void
    {
        $nurse = User::factory()->create();
        $nurse->assignRole('Nurse');
        $otherHospital = \App\Models\Hospital::create([
            'name' => 'Other Hospital',
            'is_active' => false,
        ]);
        $patient = Patient::create([
            'hospital_id' => $otherHospital->id,
            'mrn' => 'MRN-ANC-002',
            'first_name' => 'Unauthorised',
            'last_name' => 'Patient',
            'gender' => 'Female',
        ]);

        $this->actingAs($nurse)
            ->from(route('antenatal.create'))
            ->post(route('antenatal.store'), ['patient_id' => $patient->id])
            ->assertSessionHasErrors('patient_id');

        $this->assertDatabaseCount('antenatal_cards', 0);
    }
}
