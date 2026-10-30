<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_receives_application_admin_permissions_without_password_reset(): void
    {
        $this->seed(PermissionSeeder::class);

        $email = env('SUPER_ADMIN_EMAIL', 'Dev@Siloam.com');
        $user = User::create([
            'name' => 'Existing Super Admin',
            'email' => $email,
            'password' => Hash::make('existing-account-password'),
            'email_verified_at' => now(),
        ]);
        $passwordHash = $user->password;

        $this->seed(SuperAdminSeeder::class);

        $user->refresh();

        $this->assertSame($passwordHash, $user->password);
        $this->assertTrue($user->hasRole('Admin'));
        $this->assertTrue($user->hasRole('super-admin'));
        $this->assertTrue($user->can('roles.manage'));
        $this->assertTrue($user->can('admissions.manage'));
        $this->assertTrue(Role::findByName('super-admin')->hasPermissionTo('roles.manage'));
        $this->assertTrue(Role::findByName('super-admin')->hasPermissionTo('admissions.manage'));
    }

    public function test_super_admin_seeder_requires_a_password_only_to_create_a_new_user(): void
    {
        $this->seed(PermissionSeeder::class);

        if (env('SUPER_ADMIN_PASSWORD')) {
            $this->markTestSkipped('SUPER_ADMIN_PASSWORD is configured in this environment.');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SUPER_ADMIN_PASSWORD must be configured');

        $this->seed(SuperAdminSeeder::class);
    }

    public function test_super_admin_role_can_access_admin_pages_without_the_admin_role(): void
    {
        $this->seed(PermissionSeeder::class);

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($superAdminRole);

        $this->actingAs($user)
            ->get('/settings/roles')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.roles', ['super-admin'])
                ->where('auth.permissions', []));
    }
}
