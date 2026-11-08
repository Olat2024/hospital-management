<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(['antenatal.view', 'antenatal.manage'])
            ->map(fn (string $name) => Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]));

        foreach (['Receptionist', 'Nurse'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = Permission::query()
            ->whereIn('name', ['antenatal.view', 'antenatal.manage'])
            ->where('guard_name', 'web')
            ->get();

        Role::query()
            ->whereIn('name', ['Receptionist', 'Nurse'])
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permissions));

        Permission::query()
            ->whereIn('name', ['antenatal.view', 'antenatal.manage'])
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
