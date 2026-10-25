<?php

namespace Database\Seeders;

use App\Constants\Permissions;
use App\Enums\UserType;
use App\Models\Facility;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\PlanAccessType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MinimalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Cleanup existing tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('payments')->truncate();
        DB::table('subscriptions')->truncate();
        DB::table('plans')->truncate();
        DB::table('plan_access_types')->truncate();
        DB::table('facilities')->truncate();
        DB::table('role_permissions')->truncate();
        DB::table('user_roles')->truncate();
        DB::table('permissions')->truncate();
        DB::table('roles')->truncate();
        DB::table('admin_profiles')->truncate();
        DB::table('customer_profiles')->truncate();
        DB::table('users')->truncate();
        DB::table('activity_logs')->truncate();
        DB::table('notifications')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Seed Permissions
        $definedPermissions = Permissions::getAll();
        $permissionIds = [];

        foreach ($definedPermissions as $section) {
            if (isset($section['sub_modules'])) {
                foreach ($section['sub_modules'] as $name => $details) {
                    $permission = Permission::firstOrCreate(['name' => $name]);
                    if (! in_array($permission->id, $permissionIds)) {
                        $permissionIds[] = $permission->id;
                    }
                }
            }
        }

        // 3. Seed Roles

        $adminRole = Role::create([
            'name' => 'Admin',
            'slug' => UserType::ADMIN->value,
            'description' => 'System administration',
        ]);

        // Sync permissions
        $adminRole->permissions()->sync($permissionIds);
        // Customer role gets no permissions by default in this minimal seeder

        // 4. Seed Users
        $password = Hash::make('12345678');

        $superAdmin = User::create([
            'name' => 'System Super Admin',
            'email' => 'superadmin@admin.com',
            'password' => $password,
            'type' => UserType::SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $superAdmin->adminProfile()->create();

        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@admin.com',
            'password' => $password,
            'type' => UserType::ADMIN,
            'email_verified_at' => now(),
        ]);
        $admin->adminProfile()->create();
        $admin->roles()->attach($adminRole);

        $customer = User::create([
            'name' => 'System Customer',
            'email' => 'customer@admin.com',
            'password' => $password,
            'type' => UserType::CUSTOMER,
            'email_verified_at' => now(),
        ]);
        $customer->customerProfile()->create();

        // 5. Seed Facilities
        $facilities = [
            ['name' => 'Cardio Room', 'description' => 'Access to treadmill and bikes', 'is_default' => true],
            ['name' => 'Weight Area', 'description' => 'Access to free weights and machines', 'is_default' => true],
            ['name' => 'Locker Room', 'description' => 'Personal storage during workout', 'is_default' => true],
            ['name' => 'Shower', 'description' => 'Post-workout hygiene facilities', 'is_default' => true],
        ];

        foreach ($facilities as $facility) {
            Facility::create($facility);
        }

        // 6. Seed Plan Access Types
        $accessTypes = [
            [
                'name' => 'Anytime Access',
                'slug' => 'anytime-access',
                'description' => '24/7 access to the facilities',
                'is_anytime' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Basic Access',
                'slug' => 'basic-access',
                'description' => 'Access during standard business hours',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'is_anytime' => false,
                'is_active' => true,
            ],
        ];

        foreach ($accessTypes as $type) {
            PlanAccessType::create($type);
        }

        // 7. Seed Plans
        Plan::create([
            'name' => 'Silver Plan',
            'price' => 49.99,
            'duration_days' => 30,
            'description' => 'Standard monthly membership',
            'facilities' => ['Cardio Room', 'Locker Room', 'Shower'],
            'access_type' => 'basic-access',
            'is_active' => true,
            'is_default' => true,
        ]);

        Plan::create([
            'name' => 'Gold Plan',
            'price' => 79.99,
            'duration_days' => 30,
            'description' => 'Premium monthly membership with full access',
            'facilities' => ['Cardio Room', 'Weight Area', 'Locker Room', 'Shower'],
            'access_type' => 'anytime-access',
            'is_active' => true,
            'is_default' => false,
        ]);
    }
}
