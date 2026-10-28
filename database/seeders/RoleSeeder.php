<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        // Create a default Admin user
        $admin = User::firstOrCreate([
            'email' => 'admin@hospital.com',
        ], [
            'name' => 'System Admin',
            'password' => Hash::make('password123'),
        ]);

        $admin->syncRoles([Role::findByName('Admin', 'web')]);
    }
}
