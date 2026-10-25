<?php

use App\Enums\UserType;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

test('customer can switch to another plan with third-party payment and get active access', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $basicPlan = Plan::factory()->create([
        'name' => 'Monthly Basic',
        'price' => 60,
    ]);

    $switchPlan = Plan::factory()->create([
        'name' => 'Premium Plus',
        'price' => 95,
    ]);

    Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $basicPlan->id,
        'status' => 'active',
        'end_date' => now()->addDays(10)->toDateString(),
    ]);

    $this->actingAs($customer)
        ->post(route('customer.subscriptions.store'), [
            'plan_id' => $switchPlan->id,
            'payment_method' => 'third_party',
            'action' => 'switch',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Plan activated successfully.');

    $subscription = Subscription::query()
        ->where('user_id', $customer->id)
        ->where('plan_id', $switchPlan->id)
        ->latest('id')
        ->firstOrFail();

    expect($subscription->status)->toBe('active');

    $this->assertDatabaseHas('payments', [
        'subscription_id' => $subscription->id,
        'method' => 'third_party',
        'status' => 'completed',
        'transaction_type' => 'new',
    ]);
});

test('customer renew with cash creates pending plan and does not activate immediately', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create([
        'name' => 'Monthly Basic',
        'price' => 70,
    ]);

    Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'end_date' => now()->addDays(15)->toDateString(),
    ]);

    $this->actingAs($customer)
        ->post(route('customer.subscriptions.store'), [
            'plan_id' => $plan->id,
            'payment_method' => 'cash',
            'action' => 'renew',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Plan request submitted. Cash payment is pending admin approval.');

    $pendingSubscription = Subscription::query()
        ->where('user_id', $customer->id)
        ->where('plan_id', $plan->id)
        ->where('status', 'pending')
        ->latest('id')
        ->firstOrFail();

    $this->assertDatabaseHas('payments', [
        'subscription_id' => $pendingSubscription->id,
        'method' => 'cash',
        'status' => 'pending',
        'transaction_type' => 'renew',
    ]);

    expect($customer->fresh()->activeSubscriptions()->count())->toBe(1);
});

test('customer transactions history shows only own payments', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $otherCustomer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create();

    $customerSubscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'end_date' => now()->addDays(5)->toDateString(),
    ]);

    $otherSubscription = Subscription::factory()->create([
        'user_id' => $otherCustomer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'end_date' => now()->addDays(5)->toDateString(),
    ]);

    Payment::query()->create([
        'subscription_id' => $customerSubscription->id,
        'amount' => 50,
        'method' => 'third_party',
        'status' => 'completed',
        'transaction_type' => 'new',
    ]);

    Payment::query()->create([
        'subscription_id' => $otherSubscription->id,
        'amount' => 60,
        'method' => 'cash',
        'status' => 'pending',
        'transaction_type' => 'renew',
    ]);

    $this->actingAs($customer)
        ->get(route('customer.transactions.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Customer/Pages/Transactions')
            ->has('payments.data', 1)
            ->where('payments.data.0.transaction_type', 'new')
        );
});
