<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $adminRole = Role::findByName('Admin', 'web');
        $email = env('SUPER_ADMIN_EMAIL', 'Dev@Siloam.com');
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $password = env('SUPER_ADMIN_PASSWORD');

            if (! $password) {
                throw new RuntimeException('SUPER_ADMIN_PASSWORD must be configured to create the super-admin account.');
            }

            $user = User::create([
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);
        } elseif (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $user->assignRole($role, $adminRole);
    }
}
