<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;

class RoleController extends Controller
{
    public function __construct(protected \App\Services\RoleService $roleService) {}

    public function index(): \Inertia\Response
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::ACCESS_ROLES);

        return inertia('Admin/Roles/Index', [
            'roles' => $this->roleService->getAll(),
            'permissions' => \App\Constants\Permissions::getAll(),
        ]);
    }

    public function create(): \Inertia\Response
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::ACCESS_ROLES);

        return inertia('Admin/Roles/Create', [
            'permissions' => \App\Constants\Permissions::getAll(),
        ]);
    }

    public function store(\App\Http\Requests\Admin\StoreRoleRequest $request): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::ACCESS_ROLES);

        $this->roleService->create($request->validated());

        return to_route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): \Inertia\Response
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::ACCESS_ROLES);

        if ($role->slug === 'super-admin') {
            abort(403, 'The Super Admin role is immutable.');
        }

        return inertia('Admin/Roles/Edit', [
            'role' => array_merge($role->toArray(), [
                'permissions' => $role->permissions()->pluck('name'),
            ]),
            'permissions' => \App\Constants\Permissions::getAll(),
        ]);
    }

    public function update(\App\Http\Requests\Admin\UpdateRoleRequest $request, Role $role): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::ACCESS_ROLES);

        if ($role->slug === 'super-admin') {
            abort(403, 'The Super Admin role is immutable.');
        }

        $this->roleService->update($role, $request->validated());

        return to_route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::ACCESS_ROLES);

        if ($role->slug === 'super-admin') {
            abort(403, 'The Super Admin role is immutable.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete role assigned to users.');
        }

        $this->roleService->delete($role);

        return to_route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }
}
