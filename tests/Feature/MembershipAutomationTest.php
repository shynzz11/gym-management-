<?php

use App\Enums\UserType;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\MembershipExpiringSoon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('sync membership middleware expires active memberships past end date during request', function () {
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

    $this->actingAs($customer, 'web')
        ->get(route('customer.dashboard'))
        ->assertStatus(200);

    expect($subscription->refresh()->status)->toBe('expired');
});

test('membership daily check sends one warning for memberships expiring in 3 days', function () {
    Mail::fake();

    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
        'status' => 'active',
    ]);

    $plan = Plan::factory()->create([
        'duration_days' => 30,
    ]);

    $subscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'start_date' => now()->subDays(27),
        'end_date' => now()->addDays(3),
    ]);

    $this->artisan('membership:daily-check')
        ->assertExitCode(0);

    $this->artisan('membership:daily-check')
        ->assertExitCode(0);

    $notifications = $customer->notifications()
        ->where('type', MembershipExpiringSoon::class)
        ->where('data->subscription_id', $subscription->id)
        ->get();

    expect($notifications)->toHaveCount(1);
});
