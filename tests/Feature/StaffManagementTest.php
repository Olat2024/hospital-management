<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\HospitalTestSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(HospitalTestSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_admin_can_open_staff_list_and_add_a_staff_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $role = Role::findByName('Pharmacy');

        $this->actingAs($admin)
            ->get(route('staff.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/Index')
                ->has('staff.data', 4));

        $this->actingAs($admin)
            ->get(route('staff.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/Create')
                ->has('roles', 4)
                ->where('roles.0.name', 'Lab Technician')
                ->where('roles.1.name', 'Nurse')
                ->where('roles.2.name', 'Pharmacy')
                ->where('roles.3.name', 'Receptionist'));

        $this->actingAs($admin)
            ->post(route('staff.store'), [
                'name' => 'Rita Pharmacist',
                'email' => 'rita@example.test',
                'role_id' => $role->id,
                'password' => 'secure-pass-123',
                'password_confirmation' => 'secure-pass-123',
            ])
            ->assertRedirect(route('staff.index'))
            ->assertSessionHas('success');

        $staff = User::where('email', 'rita@example.test')->firstOrFail();
        $this->assertTrue($staff->hasRole('Pharmacy'));
        $this->assertNotNull($staff->email_verified_at);
        $this->assertTrue($staff->is_active);
    }

    public function test_clinical_support_roles_receive_limited_default_permissions(): void
    {
        $pharmacy = Role::findByName('Pharmacy');
        $labTechnician = Role::findByName('Lab Technician');
        $nurse = Role::findByName('Nurse');

        $this->assertEqualsCanonicalizing(
            ['patients.view', 'medical-records.view', 'pharmacy.view', 'pharmacy.manage'],
            $pharmacy->permissions->pluck('name')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['patients.view', 'medical-records.view'],
            $labTechnician->permissions->pluck('name')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['patients.view', 'appointments.view', 'medical-records.view', 'nursing.view', 'nursing.manage'],
            $nurse->permissions->pluck('name')->all()
        );
    }

    public function test_staff_creation_cannot_assign_admin_or_doctor_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        foreach (['Admin', 'Doctor'] as $roleName) {
            $this->actingAs($admin)
                ->from(route('staff.create'))
                ->post(route('staff.store'), [
                    'name' => 'Restricted Role',
                    'email' => strtolower($roleName).'@example.test',
                    'role_id' => Role::findByName($roleName)->id,
                    'password' => 'secure-pass-123',
                    'password_confirmation' => 'secure-pass-123',
                ])
                ->assertSessionHasErrors('role_id');
        }

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.test']);
        $this->assertDatabaseMissing('users', ['email' => 'doctor@example.test']);
    }

    public function test_non_admin_cannot_manage_staff_accounts(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole('Receptionist');

        $this->actingAs($receptionist)->get(route('staff.index'))->assertForbidden();
        $this->actingAs($receptionist)->get(route('staff.create'))->assertForbidden();
        $this->actingAs($receptionist)->post(route('staff.store'), [])->assertForbidden();
    }

    public function test_admin_can_edit_staff_details_role_and_account_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $staff = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.test']);
        $staff->assignRole('Receptionist');

        $this->actingAs($admin)
            ->get(route('staff.edit', $staff))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/Edit')
                ->where('staff.id', $staff->id)
                ->where('staff.roles.0.name', 'Receptionist'));

        $this->actingAs($admin)
            ->put(route('staff.update', $staff), [
                'name' => 'Updated Name',
                'email' => 'updated@example.test',
                'role_id' => Role::findByName('Nurse')->id,
                'is_active' => false,
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('staff.index'))
            ->assertSessionHas('success');

        $this->assertSame('Updated Name', $staff->fresh()->name);
        $this->assertSame('updated@example.test', $staff->fresh()->email);
        $this->assertFalse($staff->fresh()->is_active);
        $this->assertTrue($staff->fresh()->hasRole('Nurse'));
        $this->assertFalse($staff->fresh()->hasRole('Receptionist'));
    }

    public function test_admin_can_delete_staff_without_payment_audit_history(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $staff = User::factory()->create();
        $staff->assignRole('Receptionist');

        $this->actingAs($admin)
            ->delete(route('staff.destroy', $staff))
            ->assertRedirect(route('staff.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
    }

    public function test_staff_account_with_payment_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $staff = User::factory()->create();
        $patient = Patient::create([
            'hospital_id' => Hospital::default()->id,
            'mrn' => 'MRN-STAFF-PAYMENT-1',
            'first_name' => 'Payment',
            'last_name' => 'Audit',
            'dob' => '1990-01-01',
            'gender' => 'Other',
            'phone' => '08030000001',
            'address' => '1 Clinic Road',
        ]);
        $invoice = Invoice::create([
            'hospital_id' => $patient->hospital_id,
            'patient_id' => $patient->id,
            'invoice_number' => 'INV-STAFF-AUDIT-1',
            'status' => 'paid',
            'subtotal' => '10.00',
            'discount' => '0.00',
            'total' => '10.00',
            'amount_paid' => '10.00',
            'issued_at' => today(),
        ]);
        $invoice->payments()->create([
            'recorded_by' => $staff->id,
            'amount' => '10.00',
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('staff.index'))
            ->delete(route('staff.destroy', $staff))
            ->assertSessionHasErrors('staff');

        $this->assertDatabaseHas('users', ['id' => $staff->id]);
    }

    public function test_staff_account_with_pharmacy_audit_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $staff = User::factory()->create();
        $medicine = Medicine::create([
            'hospital_id' => Hospital::default()->id,
            'name' => 'Audited medicine',
            'stock_quantity' => 5,
            'reorder_level' => 1,
        ]);
        $medicine->stockMovements()->create([
            'recorded_by' => $staff->id,
            'type' => 'received',
            'quantity' => 5,
            'recorded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('staff.index'))
            ->delete(route('staff.destroy', $staff))
            ->assertSessionHasErrors('staff');

        $this->assertDatabaseHas('users', ['id' => $staff->id]);
    }
}
