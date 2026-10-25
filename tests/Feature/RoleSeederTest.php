<?php

use App\Enums\UserType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

test('role seeder ensures core roles exist by slug', function () {
    $this->seed(RoleSeeder::class);

    expect(Role::query()->where('slug', UserType::SUPER_ADMIN->value)->exists())->toBeTrue()
        ->and(Role::query()->where('slug', UserType::ADMIN->value)->exists())->toBeTrue()
        ->and(Role::query()->where('slug', UserType::CUSTOMER->value)->exists())->toBeTrue();
});

test('role seeder assigns only admin role based on user type', function () {
    $superAdmin = User::factory()->create(['type' => UserType::SUPER_ADMIN]);
    $admin = User::factory()->create(['type' => UserType::ADMIN]);
    $customer = User::factory()->create(['type' => UserType::CUSTOMER]);

    $superAdminRole = Role::query()->create([
        'name' => 'Super Admin Seeded',
        'slug' => UserType::SUPER_ADMIN->value,
        'description' => 'tmp',
    ]);
    $customerRole = Role::query()->create([
        'name' => 'Customer Seeded',
        'slug' => UserType::CUSTOMER->value,
        'description' => 'tmp',
    ]);

    $superAdmin->roles()->sync([$superAdminRole->id]);
    $customer->roles()->sync([$customerRole->id]);

    $this->seed(RoleSeeder::class);

    expect($superAdmin->fresh()->roles()->exists())->toBeFalse()
        ->and($admin->fresh()->roles()->where('slug', UserType::ADMIN->value)->exists())->toBeTrue()
        ->and($customer->fresh()->roles()->exists())->toBeFalse();
});

test('super admin always has admin panel access', function () {
    $superAdmin = User::factory()->create(['type' => UserType::SUPER_ADMIN]);

    expect(Gate::forUser($superAdmin)->allows('access-admin-panel'))->toBeTrue();
});

test('permission seeder restores missing permission rows without deleting existing permissions', function () {
    Permission::create(['name' => 'custom_permission']);
    $seeder = app()->make(PermissionSeeder::class);
    $seeder->run();
    $seeder->run();

    expect(Permission::whereIn('name', \App\Constants\Permissions::getPermissionNames())->count())
        ->toBe(count(\App\Constants\Permissions::getPermissionNames()))
        ->and(Permission::where('name', 'custom_permission')->exists())->toBeTrue();
});

test('role permission updates create missing permission rows before syncing', function () {
    $this->seed(RoleSeeder::class);

    $role = Role::create([
        'name' => 'Operations Manager',
        'slug' => 'operations-manager',
        'description' => 'Operations manager role',
    ]);

    $service = app()->make(\App\Services\RoleService::class);
    $service->update($role, [
        'name' => 'Operations Manager',
        'description' => 'Operations manager role',
        'permissions' => [
            \App\Constants\Permissions::DASHBOARD_VIEW,
            \App\Constants\Permissions::MEMBER_DIRECTORY,
            \App\Constants\Permissions::VIEW_REPORTS,
        ],
    ]);

    expect($role->fresh()->permissions()->pluck('name')->all())
        ->toContain(
            \App\Constants\Permissions::DASHBOARD_VIEW,
            \App\Constants\Permissions::MEMBER_DIRECTORY,
            \App\Constants\Permissions::VIEW_REPORTS,
        )
        ->and(\App\Models\Permission::whereIn('name', [
            \App\Constants\Permissions::DASHBOARD_VIEW,
            \App\Constants\Permissions::MEMBER_DIRECTORY,
            \App\Constants\Permissions::VIEW_REPORTS,
        ])->count())->toBe(3);
});
