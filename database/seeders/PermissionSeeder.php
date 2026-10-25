<?php

namespace Database\Seeders;

use App\Constants\Permissions;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permissions::getPermissionNames() as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }
    }
}
