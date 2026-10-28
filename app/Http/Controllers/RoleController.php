<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Access control list: roles, the permissions they grant, and custom permissions.
 */
class RoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Roles', [
            'roles' => Role::query()
                ->with('permissions:id,name')
                ->withCount('users')
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'users_count' => $role->users_count,
                    'permissions' => $role->permissions
                        ->map(fn (Permission $permission) => [
                            'id' => $permission->id,
                            'name' => $permission->name,
                        ])
                        ->values(),
                ]),
            'permissionGroups' => Permission::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->groupBy(fn (Permission $permission) => explode('.', $permission->name)[0])
                ->map(fn ($permissions, $group) => [
                    'group' => $group,
                    'permissions' => $permissions->values(),
                ])
                ->values(),
            'permissionCount' => Permission::count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::create(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()
            ->route('settings.roles')
            ->with('success', "Role {$role->name} created.");
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100', 'unique:roles,name,'.$role->id],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        if (array_key_exists('name', $validated)) {
            $role->update(['name' => $validated['name']]);
        }
        $role->syncPermissions($validated['permissions']);

        return redirect()
            ->route('settings.roles')
            ->with('success', "Permissions for {$role->name} saved.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'Admin') {
            return back()->withErrors(['role' => 'The Admin role cannot be deleted.']);
        }

        $role->delete();

        return redirect()
            ->route('settings.roles')
            ->with('success', 'Role deleted.');
    }

    public function storePermission(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                'regex:/^[a-z0-9\-]+\.[a-z0-9\-]+$/',
                'unique:permissions,name',
            ],
        ]);

        Permission::create(['name' => $validated['name']]);

        return redirect()
            ->route('settings.roles')
            ->with('success', "Permission {$validated['name']} added.");
    }
}
