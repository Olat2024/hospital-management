<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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
        'family-cards' => ['manage'],
        'appointments' => ['view', 'create', 'manage'],
        'medical-records' => ['view', 'create'],
        'referrals' => ['create'],
        'admissions' => ['view', 'manage'],
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
            'family-cards.manage',
            'appointments.view', 'appointments.create',
            'medical-records.view',
            'admissions.view', 'admissions.manage',
            'billing.view', 'billing.create', 'billing.manage',
            'hospital.view',
        ],
        'Doctor' => [
            'patients.view',
            'appointments.view',
            'medical-records.view', 'medical-records.create',
            'referrals.create',
            'admissions.view', 'admissions.manage',
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
            'admissions.view', 'admissions.manage',
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
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Create every permission in the matrix (idempotent).
        foreach (self::MATRIX as $resource => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name'       => "{$resource}.{$action}",
                    'guard_name' => 'web',
                ]);
            }
        }

        $all = Permission::query()->pluck('name')->all();

        // 2. Create every role (idempotent).
        $roleNames = array_unique(array_merge(
            ['Admin'],
            array_keys(self::ROLE_PERMISSIONS),
        ));

        foreach ($roleNames as $name) {
            Role::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }

        // 3. Declarative sync: every role ends up with exactly what the matrix says.
        Role::findByName('Admin', 'web')->syncPermissions($all);

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            Role::findByName($roleName, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}