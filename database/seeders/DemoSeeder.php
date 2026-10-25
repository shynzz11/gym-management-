<?php

namespace Database\Seeders;

use App\Constants\Permissions;
use App\Enums\UserType;
use App\Models\BusinessSetting;
use App\Models\Facility;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\PlanAccessType;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
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
        DB::table('business_settings')->truncate();
        DB::table('settings')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Seed Business Settings
        BusinessSetting::updateOrCreate(['key' => 'country'], ['value' => 'United States']);
        BusinessSetting::updateOrCreate(['key' => 'currency_symbol'], ['value' => '$']);
        BusinessSetting::updateOrCreate(['key' => 'timezone'], ['value' => 'America/New_York']);

        // 3. Seed Payment Gateway Settings
        $paymentSettings = [
            'STRIPE_ENABLED' => '1',
            'STRIPE_PUBLIC_KEY' => 'pk_test_51RNtlPFPAvq5r5mDxnNuK8pEJbB2g38zNsfkIeqlnLBqGhC6BkXy43foAc5m9ANhWpmLQcmGDa9JCveoqm4TqhbH00jBHBogds',
            'STRIPE_SECRET_KEY' => '',
            'STRIPE_WEBHOOK_SECRET' => 'whsec_demo_mode_webhook_secret',
            'SSLCOMMERZ_ENABLED' => '0',
            'SSLCOMMERZ_STORE_ID' => '',
            'SSLCOMMERZ_STORE_PASSWORD' => '',
            'SSLCOMMERZ_SANDBOX' => '1',
        ];

        foreach ($paymentSettings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // 4. Seed Permissions
        $definedPermissions = Permissions::getAll();
        $permissionIds = [];

        foreach ($definedPermissions as $section) {
            if (isset($section['sub_modules'])) {
                foreach ($section['sub_modules'] as $name => $details) {
                    $permission = Permission::firstOrCreate(['name' => $name]);
                    if (!in_array($permission->id, $permissionIds)) {
                        $permissionIds[] = $permission->id;
                    }
                }
            }
        }

        // 3. Seed Roles

        $adminRole = Role::create([
            'name' => 'Admin Manager',
            'slug' => 'admin-manager',
            'description' => 'System administration',
        ]);

        // Sync permissions
        $adminRole->permissions()->sync($permissionIds);

        // 4. Seed Users
        $password = Hash::make('12345678');

        $superAdmin = User::create([
            'name' => 'System Super Admin',
            'email' => 'admin@admin.com',
            'password' => $password,
            'type' => UserType::SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $superAdmin->adminProfile()->create();

        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'system@admin.com',
            'password' => $password,
            'type' => UserType::ADMIN,
            'email_verified_at' => now(),
        ]);
        $admin->adminProfile()->create();
        $admin->roles()->attach($adminRole);

        $customer = User::create([
            'name' => 'System Customer',
            'email' => 'customer@customer.com',
            'password' => $password,
            'type' => UserType::CUSTOMER,
            'email_verified_at' => now(),
        ]);
        $customer->customerProfile()->create();

        // 5. Seed Facilities (10)
        $facilities = [
            ['name' => 'Cardio Room', 'description' => 'Access to treadmills and bikes', 'is_default' => true],
            ['name' => 'Weight Area', 'description' => 'Access to free weights and machines', 'is_default' => true],
            ['name' => 'Locker Room', 'description' => 'Personal storage during workout', 'is_default' => true],
            ['name' => 'Shower', 'description' => 'Post-workout hygiene facilities', 'is_default' => true],
            ['name' => 'Swimming Pool', 'description' => 'Access to olympic size pool', 'is_default' => false],
            ['name' => 'Sauna', 'description' => 'Access to steam and dry sauna', 'is_default' => false],
            ['name' => 'Basketball Court', 'description' => 'Access to indoor court', 'is_default' => false],
            ['name' => 'Smoothie Bar', 'description' => 'Access to premium nutrition drinks', 'is_default' => false],
            ['name' => 'Massage Spa', 'description' => 'Access to deep tissue massage', 'is_default' => false],
            ['name' => 'Yoga Studio', 'description' => 'Access to quiet flex studio', 'is_default' => false],
        ];

        foreach ($facilities as $facility) {
            Facility::create($facility);
        }

        $facilityNames = collect($facilities)->pluck('name')->toArray();

        // 6. Seed Plan Access Types (10)
        $accessTypes = [
            ['name' => 'Anytime Access', 'slug' => 'anytime-access', 'description' => '24/7 access', 'is_anytime' => true, 'is_active' => true],
            ['name' => 'Morning Access', 'slug' => 'morning-access', 'description' => '06:00 to 12:00', 'start_time' => '06:00:00', 'end_time' => '12:00:00', 'is_anytime' => false, 'is_active' => true],
            ['name' => 'Afternoon Access', 'slug' => 'afternoon-access', 'description' => '12:00 to 17:00', 'start_time' => '12:00:00', 'end_time' => '17:00:00', 'is_anytime' => false, 'is_active' => true],
            ['name' => 'Evening Access', 'slug' => 'evening-access', 'description' => '17:00 to 23:00', 'start_time' => '17:00:00', 'end_time' => '23:00:00', 'is_anytime' => false, 'is_active' => true],
            ['name' => 'Night Access', 'slug' => 'night-access', 'description' => '23:00 to 06:00', 'start_time' => '23:00:00', 'end_time' => '06:00:00', 'is_anytime' => false, 'is_active' => true],
            ['name' => 'Weekend Access', 'slug' => 'weekend-access', 'description' => 'Sat-Sun access', 'is_anytime' => true, 'is_active' => true],
            ['name' => 'VIP Anytime', 'slug' => 'vip-anytime', 'description' => '24/7 priority access', 'is_anytime' => true, 'is_active' => true],
            ['name' => 'Student Access', 'slug' => 'student-access', 'description' => '08:00 to 16:00', 'start_time' => '08:00:00', 'end_time' => '16:00:00', 'is_anytime' => false, 'is_active' => true],
            ['name' => 'Corporate Access', 'slug' => 'corporate-access', 'description' => '06:00 to 09:00, 17:00 to 20:00', 'is_anytime' => true, 'is_active' => true],
            ['name' => 'Off-Peak Access', 'slug' => 'off-peak-access', 'description' => '10:00 to 15:00', 'start_time' => '10:00:00', 'end_time' => '15:00:00', 'is_anytime' => false, 'is_active' => true],
        ];

        foreach ($accessTypes as $type) {
            PlanAccessType::create($type);
        }

        // 7. Seed Plans (10)
        $plansConfig = [
            ['name' => 'Bronze Tier', 'price' => 19.99, 'duration' => 30, 'access' => 'off-peak-access', 'facs' => array_slice($facilityNames, 0, 3)],
            ['name' => 'Silver Tier', 'price' => 29.99, 'duration' => 30, 'access' => 'student-access', 'facs' => array_slice($facilityNames, 0, 4)],
            ['name' => 'Gold Tier', 'price' => 49.99, 'duration' => 30, 'access' => 'morning-access', 'facs' => array_slice($facilityNames, 0, 5)],
            ['name' => 'Platinum Tier', 'price' => 79.99, 'duration' => 30, 'access' => 'anytime-access', 'facs' => array_slice($facilityNames, 0, 7)],
            ['name' => 'Diamond Tier', 'price' => 99.99, 'duration' => 30, 'access' => 'vip-anytime', 'facs' => $facilityNames],
            ['name' => 'Weekly Pass', 'price' => 14.99, 'duration' => 7, 'access' => 'anytime-access', 'facs' => array_slice($facilityNames, 0, 4)],
            ['name' => 'Annual Saver', 'price' => 399.99, 'duration' => 365, 'access' => 'anytime-access', 'facs' => array_slice($facilityNames, 0, 6)],
            ['name' => 'Summer Special', 'price' => 89.99, 'duration' => 90, 'access' => 'anytime-access', 'facs' => array_slice($facilityNames, 0, 5)],
            ['name' => 'Night Owl Pass', 'price' => 24.99, 'duration' => 30, 'access' => 'night-access', 'facs' => array_slice($facilityNames, 0, 4)],
            ['name' => 'Weekend Warrior', 'price' => 19.99, 'duration' => 30, 'access' => 'weekend-access', 'facs' => array_slice($facilityNames, 0, 5)],
        ];

        $createdPlans = [];
        foreach ($plansConfig as $idx => $p) {
            $createdPlans[] = Plan::create([
                'name' => $p['name'],
                'price' => $p['price'],
                'duration_days' => $p['duration'],
                'description' => 'Demo plan description for ' . $p['name'],
                'facilities' => $p['facs'],
                'access_type' => $p['access'],
                'is_active' => true,
                'is_default' => $idx === 3, // Platinum is default
                'is_offer' => $idx === 7, // Summer Special has offer
                'offer_price' => $idx === 7 ? 69.99 : null,
                'offer_ends_at' => $idx === 7 ? now()->addDays(15) : null,
            ]);
        }

        // 8. Add Subscriptions and Payments for the Customer

        // Active Subscription
        $sub1 = Subscription::create([
            'user_id' => $customer->id,
            'plan_id' => $createdPlans[3]->id, // Platinum Tier
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(20),
            'status' => 'active',
        ]);
        Payment::create([
            'subscription_id' => $sub1->id,
            'amount' => 79.99,
            'method' => 'stripe',
            'status' => 'completed',
            'transaction_type' => 'new',
            'gateway' => 'stripe',
        ]);

        // Expired Subscription
        $sub2 = Subscription::create([
            'user_id' => $customer->id,
            'plan_id' => $createdPlans[5]->id, // Weekly Pass
            'start_date' => now()->subDays(40),
            'end_date' => now()->subDays(33),
            'status' => 'expired',
        ]);
        Payment::create([
            'subscription_id' => $sub2->id,
            'amount' => 14.99,
            'method' => 'cash',
            'status' => 'completed',
            'transaction_type' => 'new',
            'gateway' => 'cash',
        ]);

        // Pending Subscription
        $sub3 = Subscription::create([
            'user_id' => $customer->id,
            'plan_id' => $createdPlans[4]->id, // Diamond Tier
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'status' => 'pending',
        ]);
        Payment::create([
            'subscription_id' => $sub3->id,
            'amount' => 99.99,
            'method' => 'cash',
            'status' => 'pending',
            'transaction_type' => 'new',
            'gateway' => 'cash',
        ]);
    }
}