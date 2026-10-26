<?php

use App\Enums\UserType;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('membership expiry command expires active memberships past end date', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
        'status' => 'active',
    ]);

    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'start_date' => now()->subDays(31),
        'end_date' => now()->subDay(),
    ]);

    $this->artisan('membership:check-expiry')
        ->assertExitCode(0);

    expect($subscription->refresh()->status)->toBe('expired');
});
