<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\HospitalTestSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PharmacyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $pharmacist;

    protected Patient $patient;

    protected MedicalRecord $prescription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(HospitalTestSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->pharmacist = User::factory()->create();
        $this->pharmacist->assignRole('Pharmacy');

        $this->patient = Patient::create([
            'hospital_id' => Hospital::default()->id,
            'mrn' => 'MRN-PHARMACY-00001',
            'first_name' => 'Amara',
            'last_name' => 'Patient',
            'dob' => '1990-01-01',
            'gender' => 'Female',
            'phone' => '08030000001',
            'address' => '1 Clinic Road',
        ]);

        $doctor = Doctor::whereHas('user', fn ($query) => $query->where('email', 'doctor@hospital.com'))
            ->firstOrFail();
        $this->prescription = MedicalRecord::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Test diagnosis',
            'prescription' => "Confirm prescribed medicine and strength with the doctor's note.",
        ]);
    }

    public function test_pharmacist_can_manage_inventory_and_record_dispensing(): void
    {
        $this->actingAs($this->pharmacist)
            ->get(route('pharmacy.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pharmacy/Index')
                ->where('summary.prescriptionCount', 1)
                ->where('summary.medicineCount', 0)
                ->where('summary.unitsInStock', 0)
                ->where('summary.lowStockCount', 0)
                ->where('canManage', true)
                ->has('medicines.data', 0)
                ->has('prescriptions.data', 1));

        $this->actingAs($this->pharmacist)
            ->post(route('pharmacy.medicines.store'), [
                'name' => 'Example tablets',
                'generic_name' => 'Example compound',
                'strength' => '250 mg',
                'dosage_form' => 'Tablet',
                'initial_stock' => 10,
                'reorder_level' => 3,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $medicine = Medicine::firstOrFail();
        $this->assertSame(10, $medicine->stock_quantity);
        $this->assertDatabaseHas('pharmacy_stock_movements', [
            'medicine_id' => $medicine->id,
            'type' => 'received',
            'quantity' => 10,
            'reference' => 'Opening stock',
        ]);

        $this->actingAs($this->pharmacist)
            ->post(route('pharmacy.stock.store', $medicine), [
                'quantity' => 5,
                'reference' => 'Supplier order 123',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($this->pharmacist)
            ->post(route('pharmacy.dispense', $this->prescription), [
                'notes' => 'Dispensed after prescription verification.',
                'items' => [
                    ['medicine_id' => $medicine->id, 'quantity' => 4],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(11, $medicine->fresh()->stock_quantity);
        $this->assertDatabaseHas('pharmacy_dispensation_items', [
            'medicine_id' => $medicine->id,
            'quantity' => 4,
        ]);
        $this->assertDatabaseHas('pharmacy_stock_movements', [
            'medicine_id' => $medicine->id,
            'type' => 'dispensed',
            'quantity' => 4,
            'dispensation_id' => $this->prescription->dispensations()->first()->id,
        ]);
    }

    public function test_pharmacy_role_receives_its_attached_permissions_when_logged_in(): void
    {
        $this->actingAs($this->pharmacist)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.roles', ['Pharmacy'])
                ->where('auth.permissions', fn ($permissions) => $permissions->contains('patients.view')
                    && $permissions->contains('medical-records.view')
                    && $permissions->contains('pharmacy.view')
                    && $permissions->contains('pharmacy.manage')));

        $this->actingAs($this->pharmacist)
            ->get(route('patients.show', $this->patient))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Patients/Show')
                ->where('can.viewMedicalRecords', true)
                ->where('can.viewAppointments', false)
                ->has('patient.medical_records', 1));
    }

    public function test_pharmacy_summary_reports_active_stock_and_low_stock_counts(): void
    {
        $this->medicine($this->patient->hospital_id, 8);
        $this->medicine($this->patient->hospital_id, 2)->update(['name' => 'Low stock medicine']);

        $this->actingAs($this->pharmacist)
            ->get(route('pharmacy.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.medicineCount', 2)
                ->where('summary.unitsInStock', 10)
                ->where('summary.lowStockCount', 1));
    }

    public function test_dispensing_more_than_available_stock_is_rejected_without_partial_changes(): void
    {
        $medicine = $this->medicine($this->patient->hospital_id, 2);

        $this->actingAs($this->pharmacist)
            ->from(route('pharmacy.index'))
            ->post(route('pharmacy.dispense', $this->prescription), [
                'items' => [
                    ['medicine_id' => $medicine->id, 'quantity' => 3],
                ],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(2, $medicine->fresh()->stock_quantity);
        $this->assertDatabaseCount('pharmacy_dispensations', 0);
        $this->assertDatabaseCount('pharmacy_stock_movements', 0);
    }

    public function test_standalone_dispensing_reduces_stock_and_records_a_stock_movement(): void
    {
        $medicine = $this->medicine($this->patient->hospital_id, 8);

        $this->actingAs($this->pharmacist)
            ->post(route('pharmacy.stock.dispense', $medicine), [
                'quantity' => 3,
                'reference' => 'Walk-in patient',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(5, $medicine->fresh()->stock_quantity);
        $this->assertDatabaseHas('pharmacy_stock_movements', [
            'medicine_id' => $medicine->id,
            'recorded_by' => $this->pharmacist->id,
            'type' => 'dispensed',
            'quantity' => 3,
            'reference' => 'Walk-in patient',
            'dispensation_id' => null,
        ]);
        $this->assertDatabaseCount('pharmacy_dispensations', 0);
    }

    public function test_standalone_dispensing_more_than_available_stock_is_rejected(): void
    {
        $medicine = $this->medicine($this->patient->hospital_id, 2);

        $this->actingAs($this->pharmacist)
            ->from(route('pharmacy.index'))
            ->post(route('pharmacy.stock.dispense', $medicine), [
                'quantity' => 3,
            ])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(2, $medicine->fresh()->stock_quantity);
        $this->assertDatabaseCount('pharmacy_stock_movements', 0);
    }

    public function test_pharmacy_access_is_limited_to_pharmacy_and_admin_roles(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole('Receptionist');

        $this->actingAs($receptionist)->get(route('pharmacy.index'))->assertForbidden();
        $this->actingAs($receptionist)->post(route('pharmacy.medicines.store'), [])->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $this->actingAs($admin)
            ->get(route('pharmacy.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pharmacy/Index')
                ->where('canManage', true));
    }

    public function test_pharmacy_does_not_show_or_dispense_another_hospitals_prescriptions(): void
    {
        $otherHospital = Hospital::create(['name' => 'Other Hospital', 'is_active' => false]);
        $otherPatient = Patient::create([
            'hospital_id' => $otherHospital->id,
            'mrn' => 'MRN-PHARMACY-OTHER',
            'first_name' => 'Other',
            'last_name' => 'Patient',
            'dob' => '1990-01-01',
            'gender' => 'Other',
            'phone' => '08030000002',
            'address' => '2 Clinic Road',
        ]);
        $otherMedicine = $this->medicine($otherHospital->id, 10);

        $this->actingAs($this->pharmacist)
            ->get(route('pharmacy.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('medicines.data', 0)
                ->where('summary.prescriptionCount', 1));

        $this->actingAs($this->pharmacist)
            ->post(route('pharmacy.dispense', $this->prescription), [
                'items' => [
                    ['medicine_id' => $otherMedicine->id, 'quantity' => 1],
                ],
            ])
            ->assertNotFound();

        $this->assertSame(10, $otherMedicine->fresh()->stock_quantity);
        $this->assertDatabaseCount('pharmacy_dispensations', 0);
    }

    private function medicine(int $hospitalId, int $stock): Medicine
    {
        return Medicine::create([
            'hospital_id' => $hospitalId,
            'name' => 'Sample medicine',
            'strength' => '100 mg',
            'dosage_form' => 'Capsule',
            'stock_quantity' => $stock,
            'reorder_level' => 2,
            'is_active' => true,
        ]);
    }
}
