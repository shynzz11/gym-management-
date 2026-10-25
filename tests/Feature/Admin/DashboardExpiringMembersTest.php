<?php

use App\Enums\UserType;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('admin dashboard lists active memberships expiring today through day five with normalized Indian phone', function () {
    $this->travelTo(now()->setTime(12, 0));

    $admin = User::factory()->create([
        'type' => UserType::SUPER_ADMIN,
        'status' => 'active',
    ]);
    $plan = Plan::factory()->create();

    $member = User::factory()->create(['type' => UserType::CUSTOMER]);
    $member->customerProfile()->create(['phone' => '+91 (98765)-43210']);

    Subscription::factory()->create([
        'user_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'end_date' => now()->addDays(4)->toDateString(),
    ]);
    Subscription::factory()->create([
        'user_id' => $member->id,
        'plan_id' => Plan::factory()->create()->id,
        'status' => 'active',
        'end_date' => now()->addDays(2)->toDateString(),
    ]);

    $expiresToday = User::factory()->create(['type' => UserType::CUSTOMER]);
    $expiresToday->customerProfile()->create(['phone' => '098765 43211']);
    Subscription::factory()->create([
        'user_id' => $expiresToday->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'end_date' => now()->toDateString(),
    ]);

    $expiresAfterWindow = User::factory()->create(['type' => UserType::CUSTOMER]);
    $expiresAfterWindow->customerProfile()->create(['phone' => '9876543212']);
    Subscription::factory()->create([
        'user_id' => $expiresAfterWindow->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'end_date' => now()->addDays(6)->toDateString(),
    ]);

    $expired = User::factory()->create(['type' => UserType::CUSTOMER]);
    $expired->customerProfile()->create(['phone' => '9876543213']);
    Subscription::factory()->create([
        'user_id' => $expired->id,
        'plan_id' => $plan->id,
        'status' => 'expired',
        'end_date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Pages/Dashboard')
            ->has('expiring_members', 2)
            ->where('expiring_members.0.id', $expiresToday->id)
            ->where('expiring_members.0.phone', '919876543211')
            ->where('expiring_members.0.end_date', now()->toDateString())
            ->where('expiring_members.1.id', $member->id)
            ->where('expiring_members.1.phone', '919876543210')
            ->where('expiring_members.1.end_date', now()->addDays(2)->toDateString())
            ->where('stats.expiring_soon', 2)
        );
});

test('dashboard keeps an expiring member with invalid phone but provides no normalized phone', function () {
    $admin = User::factory()->create([
        'type' => UserType::SUPER_ADMIN,
        'status' => 'active',
    ]);
    $member = User::factory()->create(['type' => UserType::CUSTOMER]);
    $member->customerProfile()->create(['phone' => 'not a valid phone']);
    $plan = Plan::factory()->create();

    Subscription::factory()->create([
        'user_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'end_date' => now()->addDays(3)->toDateString(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('expiring_members', 1)
            ->where('expiring_members.0.phone', null)
        );
});
