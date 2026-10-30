<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Database\Seeders\HospitalTestSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HospitalTestSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        return $user;
    }

    public function test_admin_can_view_the_acl_screen(): void
    {
        $this->actingAs($this->admin())
            ->get('/settings/roles')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Roles')
                ->has('roles')
                ->has('permissionGroups')
                ->has('roles.0.permissions.0.id')
                ->has('roles.0.permissions.0.name')
                ->where('permissionCount', Permission::count()));
    }

    public function test_non_admin_cannot_access_role_management(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Receptionist');

        $this->actingAs($user)->get('/settings/roles')->assertForbidden();
        $this->actingAs($user)->post('/settings/roles', ['name' => 'Sneaky'])->assertForbidden();
        $this->actingAs($user)->post('/settings/permissions', ['name' => 'x.y'])->assertForbidden();
    }

    public function test_admin_can_create_a_role_with_selected_permissions(): void
    {
        $viewPatients = Permission::findByName('patients.view');
        $viewAppointments = Permission::findByName('appointments.view');

        $this->actingAs($this->admin())
            ->post('/settings/roles', [
                'name' => 'Ward Nurse',
                'permissions' => [$viewPatients->id, $viewAppointments->id],
            ])
            ->assertRedirect('/settings/roles')
            ->assertSessionHas('success');

        $role = Role::findByName('Ward Nurse');

        $this->assertEqualsCanonicalizing(
            ['patients.view', 'appointments.view'],
            $role->permissions->pluck('name')->all()
        );
    }

    public function test_admin_can_update_a_role_s_permissions(): void
    {
        $role = Role::create(['name' => 'Ward Clerk']);
        $role->syncPermissions(['patients.view']);

        $createAppointments = Permission::findByName('appointments.create');

        $this->actingAs($this->admin())
            ->put("/settings/roles/{$role->id}", [
                'permissions' => [$createAppointments->id],
            ])
            ->assertRedirect('/settings/roles');

        $this->assertEqualsCanonicalizing(
            ['appointments.create'],
            $role->fresh()->permissions->pluck('name')->all()
        );
    }

    public function test_duplicate_role_names_are_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post('/settings/roles', ['name' => 'Doctor'])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_role_cannot_be_deleted(): void
    {
        $adminRole = Role::findByName('Admin');

        $this->actingAs($this->admin())
            ->delete("/settings/roles/{$adminRole->id}")
            ->assertSessionHasErrors('role');

        $this->assertNotNull(Role::findByName('Admin'));
    }

    public function test_admin_can_delete_a_custom_role(): void
    {
        $role = Role::create(['name' => 'Temp']);

        $this->actingAs($this->admin())
            ->delete("/settings/roles/{$role->id}")
            ->assertRedirect('/settings/roles');

        $this->assertNull(Role::where('name', 'Temp')->first());
    }

    public function test_admin_can_add_a_custom_permission(): void
    {
        $this->actingAs($this->admin())
            ->post('/settings/permissions', ['name' => 'reports.export'])
            ->assertRedirect('/settings/roles');

        $this->assertNotNull(Permission::findByName('reports.export'));
    }

    public function test_permission_names_must_follow_the_resource_action_format(): void
    {
        $this->actingAs($this->admin())
            ->post('/settings/permissions', ['name' => 'Not A Permission'])
            ->assertSessionHasErrors('name');
    }

    public function test_permission_matrix_lists_roles_with_their_permissions(): void
    {
        $this->actingAs($this->admin())
            ->get('/settings/roles')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Roles')
                ->has('roles')
                ->where('roles', fn ($roles) => collect($roles)->pluck('name')->all() === [
                    'Admin',
                    'Doctor',
                    'Lab Technician',
                    'Nurse',
                    'Pharmacy',
                    'Receptionist',
                ])
                ->where('roles.0.name', 'Admin'));
    }

    public function test_patient_care_permissions_are_listed_and_granted_to_default_roles(): void
    {
        $this->actingAs($this->admin())
            ->get('/settings/roles')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('permissionGroups', fn ($groups) => $groups->contains(
                    fn ($group) => $group['group'] === 'family-cards'
                        && collect($group['permissions'])->contains('name', 'family-cards.manage')
                ) && $groups->contains(
                    fn ($group) => $group['group'] === 'referrals'
                        && collect($group['permissions'])->contains('name', 'referrals.create')
                ) && $groups->contains(
                    fn ($group) => $group['group'] === 'admissions'
                        && collect($group['permissions'])->contains('name', 'admissions.view')
                        && collect($group['permissions'])->contains('name', 'admissions.manage')
                )));

        $this->assertTrue(Role::findByName('Receptionist')->hasPermissionTo('family-cards.manage'));
        $this->assertTrue(Role::findByName('Doctor')->hasPermissionTo('referrals.create'));
        $this->assertTrue(Role::findByName('Doctor')->hasPermissionTo('admissions.manage'));
        $this->assertTrue(Role::findByName('Nurse')->hasPermissionTo('admissions.manage'));
    }

    public function test_permission_seeder_adds_new_permissions_to_preexisting_default_roles(): void
    {
        $doctor = Role::findByName('Doctor');
        $doctor->syncPermissions(['patients.view']);
        $receptionist = Role::findByName('Receptionist');
        $receptionist->syncPermissions(['patients.view']);

        $this->seed(PermissionSeeder::class);

        $this->assertTrue($doctor->fresh()->hasPermissionTo('referrals.create'));
        $this->assertTrue($doctor->fresh()->hasPermissionTo('admissions.manage'));
        $this->assertTrue($receptionist->fresh()->hasPermissionTo('family-cards.manage'));
        $this->assertTrue($receptionist->fresh()->hasPermissionTo('admissions.manage'));
    }
}
