<?php

use App\Enums\UserType;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);

    $this->superAdmin = User::factory()->create([
        'type' => UserType::SUPER_ADMIN,
        'status' => 'active',
    ]);
});

test('admin can approve pending payment and activate pending subscription', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create([
        'duration_days' => 15,
        'price' => 120,
    ]);

    $pendingSubscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    $pendingPayment = Payment::query()->create([
        'subscription_id' => $pendingSubscription->id,
        'amount' => $plan->price,
        'method' => 'cash',
        'status' => 'pending',
        'transaction_type' => 'new',
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->patch(route('admin.payments.status', $pendingPayment), [
            'decision' => 'approve',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Payment approved successfully.');

    $this->assertDatabaseHas('payments', [
        'id' => $pendingPayment->id,
        'status' => 'completed',
    ]);

    $pendingSubscription->refresh();

    expect($pendingSubscription->status)->toBe('active');
    expect($pendingSubscription->start_date)->not->toBeNull();
    expect($pendingSubscription->end_date)->not->toBeNull();
});

test('admin can deny pending payment with reason and expire pending subscription', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create([
        'duration_days' => 30,
        'price' => 90,
    ]);

    $pendingSubscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    $pendingPayment = Payment::query()->create([
        'subscription_id' => $pendingSubscription->id,
        'amount' => $plan->price,
        'method' => 'cash',
        'status' => 'pending',
        'transaction_type' => 'new',
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->patch(route('admin.payments.status', $pendingPayment), [
            'decision' => 'deny',
            'denial_reason' => 'Payment proof is invalid.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Payment denied successfully.');

    $this->assertDatabaseHas('payments', [
        'id' => $pendingPayment->id,
        'status' => 'canceled',
        'denial_reason' => 'Payment proof is invalid.',
    ]);

    $this->assertSoftDeleted('subscriptions', [
        'id' => $pendingSubscription->id,
    ]);
});

test('admin cannot process payment that is not pending', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $completedPayment = Payment::query()->create([
        'subscription_id' => $subscription->id,
        'amount' => $plan->price,
        'method' => 'cash',
        'status' => 'completed',
        'transaction_type' => 'new',
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->from(route('admin.payments.pending'))
        ->patch(route('admin.payments.status', $completedPayment), [
            'decision' => 'approve',
        ])
        ->assertRedirect(route('admin.payments.pending'))
        ->assertSessionHasErrors([
            'payment' => 'Only pending payments can be processed.',
        ]);

    $this->assertDatabaseHas('payments', [
        'id' => $completedPayment->id,
        'status' => 'completed',
    ]);
});
