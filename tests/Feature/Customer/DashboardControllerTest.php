<?php

use App\Enums\UserType;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanAccessType;
use App\Models\Subscription;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('customer dashboard handles access windows saved in hour-minute format', function () {
    $accessType = PlanAccessType::query()->create([
        'name' => 'Morning Access',
        'slug' => 'morning-access',
        'description' => 'Access during daytime hours.',
        'start_time' => '06:00',
        'end_time' => '12:00',
        'is_anytime' => false,
        'is_active' => true,
    ]);

    $plan = Plan::factory()->create([
        'access_type' => $accessType->slug,
        'start_time' => null,
        'end_time' => null,
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'end_date' => now()->addDays(7)->toDateString(),
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('customer.dashboard'));

    $response->assertSuccessful();
});

test('customer dashboard returns multiple active subscriptions for multi-card view', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $monthlyPlan = Plan::factory()->create([
        'name' => 'Monthly Basic',
    ]);

    $saunaPlan = Plan::factory()->create([
        'name' => 'Sauna Add-on',
    ]);

    Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $monthlyPlan->id,
        'status' => 'active',
        'end_date' => now()->addDays(14)->toDateString(),
    ]);

    Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $saunaPlan->id,
        'status' => 'active',
        'end_date' => now()->addDays(21)->toDateString(),
    ]);

    $this
        ->actingAs($customer)
        ->get(route('customer.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Customer/Pages/Dashboard')
            ->has('activeSubscriptions', 2)
            ->has('pendingRenewPlanIds')
            ->has('payments')
        );
});

test('customer dashboard exposes pending renew plan ids for renew button lock', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create([
        'name' => 'Gold Plan',
    ]);

    $subscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'end_date' => now()->addDays(20)->toDateString(),
    ]);

    Payment::query()->create([
        'subscription_id' => $subscription->id,
        'amount' => $plan->price,
        'method' => 'cash',
        'status' => 'pending',
        'transaction_type' => 'renew',
    ]);

    $this
        ->actingAs($customer)
        ->get(route('customer.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Customer/Pages/Dashboard')
            ->has('pendingRenewPlanIds', 1)
            ->where('pendingRenewPlanIds.0', $plan->id)
        );
});
