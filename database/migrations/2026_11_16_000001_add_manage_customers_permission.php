<?php

use App\Constants\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Customer management used to share the staff_directory permission, so anyone allowed to
 * manage customers could also create staff and assign roles. Split it out, and grant the new
 * permission to every role that already had staff_directory so nobody loses access on deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')->insertOrIgnore([
            'name' => Permissions::MANAGE_CUSTOMERS,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! Schema::hasTable('role_permissions')) {
            return;
        }

        $manageCustomersId = DB::table('permissions')
            ->where('name', Permissions::MANAGE_CUSTOMERS)
            ->value('id');

        $staffDirectoryId = DB::table('permissions')
            ->where('name', Permissions::MEMBER_DIRECTORY)
            ->value('id');

        if (! $manageCustomersId || ! $staffDirectoryId) {
            return;
        }

        $roleIds = DB::table('role_permissions')
            ->where('permission_id', $staffDirectoryId)
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $roleId,
                'permission_id' => $manageCustomersId,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', Permissions::MANAGE_CUSTOMERS)
            ->value('id');

        if (! $permissionId) {
            return;
        }

        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->where('permission_id', $permissionId)
                ->delete();
        }

        DB::table('permissions')
            ->where('id', $permissionId)
            ->delete();
    }
};
