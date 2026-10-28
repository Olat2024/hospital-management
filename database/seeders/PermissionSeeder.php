<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * The access-control matrix: which permissions exist and which roles hold them.
 * Re-run with `php artisan db:seed --class=PermissionSeeder` after adding
 * permissions so every role gets reviewed.
 */
class PermissionSeeder extends Seeder
{
    /**
     * Permissions are named "<resource>.<action>".
     */
    public const MATRIX = [
        'patients' => ['view', 'create', 'manage'],
        'appointments' => ['view', 'create', 'manage'],
        'medical-records' => ['view', 'create'],
        'nursing' => ['view', 'manage'],
        'doctors' => ['manage'],
        'billing' => ['view', 'create', 'manage'],
        'pharmacy' => ['view', 'manage'],
        'hospital' => ['view', 'manage'],
        'roles' => ['manage'],
        'users' => ['manage'],
    ];

    /**
     * Least-privilege defaults per role. Admin receives everything (and is
     * additionally short-circuited by Gate::before in AppServiceProvider).
     */
    public const ROLE_PERMISSIONS = [
        'Receptionist' => [
            'patients.view', 'patients.create',
            'appointments.view', 'appointments.create',
            'medical-records.view',
            'billing.view', 'billing.create', 'billing.manage',
            'hospital.view',
        ],
        'Doctor' => [
            'patients.view',
            'appointments.view',
            'medical-records.view', 'medical-records.create',
            'nursing.view',
            'billing.view',
            'hospital.view',
        ],
        'Lab Technician' => [
            'patients.view',
            'medical-records.view',
        ],
        'Nurse' => [
            'patients.view',
            'appointments.view',
            'medical-records.view',
            'nursing.view', 'nursing.manage',
        ],
        'Pharmacy' => [
            'patients.view',
            'medical-records.view',
            'pharmacy.view', 'pharmacy.manage',
        ],
    ];

    public function run(): void
    {
        foreach (self::MATRIX as $resource => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$resource}.{$action}"]);
            }
        }

        $all = Permission::query()->pluck('name')->all();

        Role::firstOrCreate(['name' => 'Admin'])->syncPermissions($all);
        Role::firstOrCreate(['name' => 'Doctor']);
        Role::firstOrCreate(['name' => 'Receptionist']);
        Role::firstOrCreate(['name' => 'Pharmacy']);
        Role::firstOrCreate(['name' => 'Lab Technician']);
        Role::firstOrCreate(['name' => 'Nurse']);

        foreach (self::ROLE_PERMISSIONS as $role => $permissions) {
            $role = Role::findByName($role, 'web');

            if ($role->name === 'Pharmacy') {
                $role->givePermissionTo($permissions);
            } elseif (! $role->permissions()->exists()) {
                $role->syncPermissions($permissions);
            }

            if (in_array($role->name, ['Doctor', 'Nurse'], true)) {
                $role->givePermissionTo(array_intersect($permissions, ['nursing.view', 'nursing.manage']));
            }
        }
    }
}
