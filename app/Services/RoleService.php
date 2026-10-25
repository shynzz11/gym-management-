<?php

namespace App\Services;

use App\Constants\Permissions;
use App\Models\Permission;
use App\Models\Role;

class RoleService
{
    public function getAll()
    {
        return Role::withCount(['users', 'permissions'])
            ->latest()
            ->get();
    }

    public function create(array $data): Role
    {
        $role = Role::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        if (isset($data['permissions'])) {
            $this->syncPermissions($role, $data['permissions'], 'assigned');
        }

        return $role;
    }

    public function update(Role $role, array $data): Role
    {
        $role->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        if (isset($data['permissions'])) {
            $this->syncPermissions($role, $data['permissions'], 'updated');
        }

        return $role;
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    protected function syncPermissions(Role $role, array $permissionNames, string $action): void
    {
        $this->ensurePermissionsExist();

        $permissionIds = Permission::whereIn('name', $permissionNames)
            ->pluck('id')
            ->all();

        $changes = $role->permissions()->sync($permissionIds);

        if (! empty($changes['attached']) || ! empty($changes['detached']) || ! empty($changes['updated'])) {
            if (method_exists($role, 'logActivity')) {
                $role->logActivity('updated', "Role permissions {$action}");
            }
        }
    }

    protected function ensurePermissionsExist(): void
    {
        foreach (Permissions::getPermissionNames() as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }
    }
}
