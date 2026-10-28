<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use App\Models\VitalSign;
use Database\Seeders\HospitalTestSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NursingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Hospital $hospital;

    protected User $nurse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(HospitalTestSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->hospital = Hospital::default();
        $this->nurse = User::factory()->create();
        $this->nurse->assignRole('Nurse');
    }

    protected function makePatient(array $overrides = []): Patient
    {
        return Patient::create(array_merge([
            'hospital_id' => $this->hospital->id,
            'mrn' => 'MRN-NURSING-'.str()->random(8),
            'first_name' => 'Amara',
            'last_name' => 'Okafor',
            'dob' => '1990-01-01',
            'gender' => 'Female',
            'phone' => '08030000001',
            'address' => '1 Clinic Road',
        ], $overrides));
    }

    public function test_nurse_can_view_patient_observations_and_hospital_summary(): void
    {
        $patient = $this->makePatient();
        $patient->vitalSigns()->create([
            'recorded_by' => $this->nurse->id,
            'systolic_blood_pressure' => 120,
            'diastolic_blood_pressure' => 80,
            'pulse_rate' => 72,
            'recorded_at' => now(),
        ]);

        $this->actingAs($this->nurse)
            ->get(route('nursing.index', ['patient_id' => $patient->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Nursing/Index')
                ->has('patients.data', 1)
                ->where('patients.data.0.latest_vital_sign.systolic_blood_pressure', 120)
                ->where('selectedPatient.id', $patient->id)
                ->where('summary.patientCount', 1)
                ->where('summary.readingsToday', 1)
                ->where('summary.patientsMeasuredToday', 1)
                ->where('canManage', true));
    }

    public function test_nurse_can_record_vitals_and_review_them_in_the_patient_chart(): void
    {
        $patient = $this->makePatient();
        $olderVital = $patient->vitalSigns()->create([
            'recorded_by' => $this->nurse->id,
            'pulse_rate' => 68,
            'recorded_at' => now()->subHour(),
        ]);

        $this->actingAs($this->nurse)
            ->post(route('nursing.vitals.store'), [
                'patient_id' => $patient->id,
                'systolic_blood_pressure' => 118,
                'diastolic_blood_pressure' => 76,
                'pulse_rate' => 70,
                'respiratory_rate' => 16,
                'temperature_celsius' => 36.7,
                'oxygen_saturation' => 98,
                'weight_kg' => 63.5,
                'height_cm' => 165,
                'pain_score' => 2,
                'notes' => 'Patient resting comfortably.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $vital = VitalSign::where('notes', 'Patient resting comfortably.')->firstOrFail();
        $this->assertSame($patient->id, $vital->patient_id);
        $this->assertSame($this->nurse->id, $vital->recorded_by);
        $this->assertSame('36.7', $vital->temperature_celsius);
        $this->assertSame('63.50', $vital->weight_kg);

        $this->actingAs($this->nurse)
            ->get(route('patients.show', $patient))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('can.viewVitals', true)
                ->where('can.manageVitals', true)
                ->has('patient.vital_signs', 2)
                ->where('patient.vital_signs.0.notes', 'Patient resting comfortably.')
                ->where('patient.vital_signs.0.recorder.name', $this->nurse->name)
                ->where('patient.vital_signs.1.id', $olderVital->id));
    }

    public function test_nurse_must_record_a_measurement_and_measurements_are_range_checked(): void
    {
        $patient = $this->makePatient();

        $this->actingAs($this->nurse)
            ->from(route('nursing.index'))
            ->post(route('nursing.vitals.store'), [
                'patient_id' => $patient->id,
                'notes' => 'No measurements entered.',
            ])
            ->assertSessionHasErrors('systolic_blood_pressure');

        $this->actingAs($this->nurse)
            ->from(route('nursing.index'))
            ->post(route('nursing.vitals.store'), [
                'patient_id' => $patient->id,
                'pulse_rate' => 999,
            ])
            ->assertSessionHasErrors('pulse_rate');

        $this->assertDatabaseCount('vital_signs', 0);
    }

    public function test_nurse_cannot_record_vitals_for_another_hospital_patient(): void
    {
        $otherHospital = Hospital::create(['name' => 'Other Hospital']);
        $patient = $this->makePatient([
            'hospital_id' => $otherHospital->id,
            'mrn' => 'MRN-OTHER-HOSPITAL-1',
        ]);

        $this->actingAs($this->nurse)
            ->from(route('nursing.index'))
            ->post(route('nursing.vitals.store'), [
                'patient_id' => $patient->id,
                'pulse_rate' => 80,
            ])
            ->assertSessionHasErrors('patient_id');

        $this->assertDatabaseCount('vital_signs', 0);
    }

    public function test_users_without_nursing_permission_cannot_open_or_write_to_the_nursing_module(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole('Receptionist');
        $patient = $this->makePatient();

        $this->actingAs($receptionist)
            ->get(route('nursing.index'))
            ->assertForbidden();

        $this->actingAs($receptionist)
            ->post(route('nursing.vitals.store'), [
                'patient_id' => $patient->id,
                'pulse_rate' => 80,
            ])
            ->assertForbidden();
    }
}
