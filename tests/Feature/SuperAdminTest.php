<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create([
        'type' => UserType::SUPER_ADMIN,
        'status' => 'active',
    ]);

    $this->admin = User::factory()->create([
        'type' => UserType::ADMIN,
        'status' => 'active',
    ]);
});

test('Supreme Power: Super Admin can access email settings while regular admin cannot', function () {
    // Assert Super Admin can access
    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.business.email.index'))
        ->assertStatus(200);

    // Assert Regular Admin (without specific permission) is blocked
    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.business.email.index'))
        ->assertStatus(403);
});

test('Hard-Coding Protection: Cannot delete super admin user (returns 403)', function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);

    $this->actingAs($this->superAdmin, 'admin')
        ->delete(route('admin.staff.destroy', $this->superAdmin))
        ->assertStatus(403);

    $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
});

test('Auto-Deactivation: check-expiry command updates expired member statuses', function () {
    $plan = Plan::factory()->create();

    // Create 3 members whose subscriptions ended yesterday
    $expiredMembers = User::factory(3)->create(['type' => UserType::CUSTOMER]);
    foreach ($expiredMembers as $member) {
        Subscription::factory()->create([
            'user_id' => $member->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'start_date' => now()->subDays(31),
            'end_date' => now()->subDay(),
        ]);
    }

    // Run the command
    $this->artisan('membership:check-expiry')
        ->assertExitCode(0);

    // Verify statuses
    foreach ($expiredMembers as $member) {
        $subscription = Subscription::where('user_id', $member->id)->first();
        expect($subscription->status)->toBe('expired');
    }
});

test('Middleware: Expired member can access dashboard and receives warning session flash', function () {
    $member = User::factory()->create(['type' => UserType::CUSTOMER]);

    // Create active subscription that should expire while processing request
    Subscription::factory()->create([
        'user_id' => $member->id,
        'status' => 'active',
        'end_date' => now()->subDay(),
    ]);

    $this->actingAs($member, 'web')
        ->get(route('customer.dashboard'))
        ->assertStatus(200)
        ->assertSessionHas('warning');

    $subscription = Subscription::query()->where('user_id', $member->id)->first();
    expect($subscription?->status)->toBe('expired');
});
