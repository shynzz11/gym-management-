<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $permissionName = 'view_pending_payments';

        $existingPermission = DB::table('permissions')
            ->where('name', $permissionName)
            ->first();

        if ($existingPermission) {
            $permissionId = $existingPermission->id;
        } else {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => $permissionName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $roleIds = DB::table('roles')
            ->whereIn('slug', ['admin', 'super-admin', 'super_admin'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            $exists = DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $permissionName = 'view_pending_payments';

        $permission = DB::table('permissions')
            ->where('name', $permissionName)
            ->first();

        if (! $permission) {
            return;
        }

        DB::table('role_permissions')
            ->where('permission_id', $permission->id)
            ->delete();

        DB::table('permissions')
            ->where('id', $permission->id)
            ->delete();
    }
};
