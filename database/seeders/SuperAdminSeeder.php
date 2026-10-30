<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Make sure the role exists
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $user = User::firstOrCreate(
            ['email' => 'Dev@Siloam.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('12345'),
                'email_verified_at' => now(),
            ]
        );

        // If user already existed, force the password and verify email
        $user->password = Hash::make('12345');
        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->save();

        $user->assignRole($role);
    }
}