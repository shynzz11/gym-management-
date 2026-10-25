<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user isActive method accurately reflects active subscriptions', function () {
    $user = User::factory()->create();
    $plan = Plan::create(['name' => 'Test Plan', 'price' => 10, 'duration_days' => 30]);

    expect($user->isActive())->toBeFalse();

    $subscription = Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'start_date' => now(),
        'end_date' => now()->addDays(30),
        'status' => 'active',
    ]);

    expect($user->isActive())->toBeTrue();

    $subscription->update(['end_date' => now()->subDay()]);
    expect($user->isActive())->toBeFalse();
});

test('subscription service calculates correct end date', function () {
    $user = User::factory()->create();
    $plan = Plan::create(['name' => 'Monthly', 'price' => 29.99, 'duration_days' => 30]);

    $service = app(SubscriptionService::class);
    $subscription = $service->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    expect($subscription->end_date->toDateString())->toBe(now()->addDays($plan->duration_days)->toDateString());
    expect($subscription->status)->toBe('active');
});
