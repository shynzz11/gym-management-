<?php

namespace Database\Seeders;

use App\Constants\Permissions;
use App\Enums\UserType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $definedPermissionNames = Permissions::getPermissionNames();
        $permissionIds = [];

        // Delete permissions that are NOT in the defined list
        Permission::whereNotIn('name', $definedPermissionNames)->delete();

        // Create or update defined permissions
        foreach ($definedPermissionNames as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $permissionIds[] = $permission->id;
        }

        $superAdminRole = Role::updateOrCreate(
            ['slug' => UserType::SUPER_ADMIN->value],
            ['name' => 'Super Admin', 'description' => 'Supreme role with full system access']
        );

        $adminRole = Role::updateOrCreate(
            ['slug' => UserType::ADMIN->value],
            ['name' => 'Admin', 'description' => 'Administrative role for gym operations']
        );

        $customerRole = Role::updateOrCreate(
            ['slug' => UserType::CUSTOMER->value],
            ['name' => 'Customer', 'description' => 'Gym member role']
        );

        $superAdminRole->permissions()->sync($permissionIds);
        $adminRole->permissions()->sync($permissionIds);
        $customerRole->permissions()->sync([]);

        User::query()
            ->where('type', UserType::ADMIN->value)
            ->each(function (User $user) use ($adminRole): void {
                $user->roles()->syncWithoutDetaching([$adminRole->id]);
            });

        User::query()
            ->whereIn('type', [UserType::SUPER_ADMIN->value, UserType::CUSTOMER->value])
            ->each(function (User $user): void {
                $user->roles()->detach();
            });
    }
}
