<?php

use App\Constants\Permissions;
use App\Enums\UserType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

function adminWithPermissions(array $permissionNames): User
{
    $admin = User::factory()->create([
        'type' => UserType::ADMIN,
        'status' => 'active',
    ]);

    $role = Role::query()->create([
        'name' => 'Role '.implode(',', $permissionNames),
        'description' => 'Permission split test role',
    ]);

    foreach ($permissionNames as $permissionName) {
        $role->permissions()->attach(Permission::query()->firstOrCreate(['name' => $permissionName]));
    }

    $admin->roles()->attach($role);

    return $admin;
}

test('customer-only staff can manage customers but cannot touch staff accounts', function () {
    $receptionist = adminWithPermissions([Permissions::MANAGE_CUSTOMERS]);
    $otherStaff = User::factory()->create(['type' => UserType::ADMIN]);
    $fullAdminRole = Role::query()->create(['name' => 'Full Admin', 'description' => 'Everything']);

    $this->actingAs($receptionist, 'admin')
        ->get(route('admin.customers.index'))
        ->assertSuccessful();

    $this->actingAs($receptionist, 'admin')
        ->get(route('admin.staff.index'))
        ->assertForbidden();

    $this->actingAs($receptionist, 'admin')
        ->post(route('admin.staff.store'), [
            'name' => 'Escalated',
            'email' => 'escalated@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $fullAdminRole->id,
        ])
        ->assertForbidden();

    $this->actingAs($receptionist, 'admin')
        ->put(route('admin.staff.update', $otherStaff), [
            'name' => $otherStaff->name,
            'email' => $otherStaff->email,
            'password' => 'takeover123',
            'password_confirmation' => 'takeover123',
        ])
        ->assertForbidden();

    $this->actingAs($receptionist, 'admin')
        ->delete(route('admin.staff.destroy', $otherStaff))
        ->assertForbidden();

    expect(User::query()->where('email', 'escalated@example.test')->exists())->toBeFalse();
    expect(User::query()->whereKey($otherStaff->id)->exists())->toBeTrue();
});

test('customer-only staff can still delete a customer through the shared destroy route', function () {
    $receptionist = adminWithPermissions([Permissions::MANAGE_CUSTOMERS]);
    $customer = User::factory()->create(['type' => UserType::CUSTOMER]);

    $this->actingAs($receptionist, 'admin')
        ->delete(route('admin.staff.destroy', $customer))
        ->assertRedirect();

    expect(User::query()->whereKey($customer->id)->exists())->toBeFalse();
});

test('staff-directory-only admin cannot manage customers', function () {
    $staffManager = adminWithPermissions([Permissions::MEMBER_DIRECTORY]);
    $customer = User::factory()->create(['type' => UserType::CUSTOMER]);

    $this->actingAs($staffManager, 'admin')
        ->get(route('admin.staff.index'))
        ->assertSuccessful();

    $this->actingAs($staffManager, 'admin')
        ->get(route('admin.customers.index'))
        ->assertForbidden();

    $this->actingAs($staffManager, 'admin')
        ->get(route('admin.customers.edit', $customer))
        ->assertForbidden();

    $this->actingAs($staffManager, 'admin')
        ->delete(route('admin.staff.destroy', $customer))
        ->assertForbidden();
});

test('manage_customers migration grants the new permission to roles that had staff_directory', function () {
    $staffDirectory = Permission::query()->firstOrCreate(['name' => Permissions::MEMBER_DIRECTORY]);
    $existingRole = Role::query()->create(['name' => 'Front Desk', 'description' => 'Pre-split role']);
    $unrelatedRole = Role::query()->create(['name' => 'Trainer', 'description' => 'No directory access']);
    $existingRole->permissions()->attach($staffDirectory);
    Permission::query()->where('name', Permissions::MANAGE_CUSTOMERS)->delete();

    $migration = require database_path('migrations/2026_11_16_000001_add_manage_customers_permission.php');
    $migration->up();

    $manageCustomersId = DB::table('permissions')->where('name', Permissions::MANAGE_CUSTOMERS)->value('id');

    expect($manageCustomersId)->not->toBeNull();
    expect($existingRole->permissions()->pluck('name')->all())->toContain(Permissions::MANAGE_CUSTOMERS);
    expect($unrelatedRole->permissions()->pluck('name')->all())->not->toContain(Permissions::MANAGE_CUSTOMERS);
});
