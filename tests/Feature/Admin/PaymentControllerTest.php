<?php

use App\Enums\UserType;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

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

test('payments index is paginated', function () {
    $subscription = Subscription::factory()->create();

    foreach (range(1, 30) as $i) {
        $subscription->payments()->create([
            'amount' => 100,
            'method' => 'cash',
            'status' => 'completed',
        ]);
    }

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.payments.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Payments/Index')
            ->has('payments.data', 25)
            ->where('payments.total', 30)
            ->where('payments.last_page', 2)
        );
});

test('payments search covers every page and matches customer name or receipt reference', function () {
    $target = User::factory()->create(['type' => UserType::CUSTOMER, 'name' => 'Ravi Kumar']);
    $targetSubscription = Subscription::factory()->create(['user_id' => $target->id]);
    $targetPayment = $targetSubscription->payments()->create([
        'amount' => 500,
        'method' => 'cash',
        'status' => 'completed',
    ]);

    // Newer payments push the target off the first page.
    $otherSubscription = Subscription::factory()->create();
    foreach (range(1, 30) as $i) {
        $otherSubscription->payments()->create([
            'amount' => 100,
            'method' => 'cash',
            'status' => 'completed',
        ]);
    }

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.payments.index', ['search' => 'ravi']))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.search', 'ravi')
            ->has('payments.data', 1)
            ->where('payments.data.0.id', $targetPayment->id)
        );

    $reference = 'PAY-'.str_pad((string) $targetPayment->id, 6, '0', STR_PAD_LEFT);

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.payments.index', ['search' => $reference]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('payments.data', fn ($payments) => collect($payments)->pluck('id')->contains($targetPayment->id))
        );
});
