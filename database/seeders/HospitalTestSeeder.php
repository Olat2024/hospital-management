<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HospitalTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        // A hospital for patients and appointments to belong to.
        Hospital::firstOrCreate(
            ['name' => 'General Hospital'],
            ['address' => '1 Hospital Road, Ijebu Ode, Ogun State', 'is_active' => true]
        );

        // Admin user.
        $adminUser = $this->user('admin@hospital.com', 'Admin User', 'password', 'Admin');
        $adminUser->assignRole('Admin');

        // Doctor user and linked doctor profile.
        $docUser = $this->user('doctor@hospital.com', 'Dr. John Doe', 'password', 'Doctor');
        $docUser->assignRole('Doctor');

        Doctor::firstOrCreate(['user_id' => $docUser->id], [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'specialization' => 'General Medicine',
            'phone' => '08012345678',
        ]);

        // Receptionist user (registers patients and books appointments).
        $this->user('receptionist@hospital.com', 'Rita Reception', 'password', 'Receptionist')
            ->assignRole('Receptionist');
    }

    protected function user(string $email, string $name, string $password, string $role): User
    {
        $user = User::firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => Hash::make($password),
        ]);

        // Local mail is logged only, so mark accounts as verified up front.
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $user;
    }
}
