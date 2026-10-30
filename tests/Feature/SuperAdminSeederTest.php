<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
}
