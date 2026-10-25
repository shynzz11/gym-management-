<?php

use App\Enums\UserType;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

test('customer cannot renew again while renew payment is pending for same plan', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create([
        'price' => 120,
        'duration_days' => 30,
    ]);

    $subscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'end_date' => now()->addDays(12)->toDateString(),
    ]);

    Payment::query()->create([
        'subscription_id' => $subscription->id,
        'amount' => $plan->price,
        'method' => 'cash',
        'status' => 'pending',
        'transaction_type' => 'renew',
    ]);

    $subscriptionsBefore = Subscription::query()->count();
    $paymentsBefore = Payment::query()->count();

    $this->actingAs($customer)
        ->from(route('customer.dashboard'))
        ->post(route('customer.payments.checkout'), [
            'plan_id' => $plan->id,
            'action' => 'renew',
            'payment_gateway' => 'cash',
        ])
        ->assertRedirect(route('customer.dashboard'))
        ->assertSessionHasErrors([
            'payment_gateway' => 'A renewal payment is already pending for this plan. Please wait for approval or cancellation before renewing again.',
        ]);

    expect(Subscription::query()->count())->toBe($subscriptionsBefore);
    expect(Payment::query()->count())->toBe($paymentsBefore);
});

test('customer renew with cash uses existing subscription without creating duplicate subscription row', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create([
        'price' => 80,
        'duration_days' => 30,
    ]);

    $subscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'end_date' => now()->addDays(7)->toDateString(),
    ]);

    $this->actingAs($customer)
        ->post(route('customer.payments.checkout'), [
            'plan_id' => $plan->id,
            'action' => 'renew',
            'payment_gateway' => 'cash',
        ])
        ->assertRedirect(route('customer.dashboard'))
        ->assertSessionHas('success', 'Plan request submitted. Cash payment is pending admin approval.');

    expect(Subscription::query()
        ->where('user_id', $customer->id)
        ->where('plan_id', $plan->id)
        ->count())->toBe(1);

    $this->assertDatabaseHas('payments', [
        'subscription_id' => $subscription->id,
        'amount' => '80.00',
        'method' => 'cash',
        'status' => 'pending',
        'transaction_type' => 'renew',
    ]);
});

test('customer checkout with free plan activates instantly and does not create pending payment', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $freePlan = Plan::factory()->create([
        'price' => 0,
        'duration_days' => 30,
    ]);

    $this->actingAs($customer)
        ->post(route('customer.payments.checkout'), [
            'plan_id' => $freePlan->id,
            'action' => 'switch',
            'payment_gateway' => 'cash',
        ])
        ->assertRedirect(route('customer.dashboard'))
        ->assertSessionHas('success', 'Free plan activated successfully.');

    $subscription = Subscription::query()
        ->where('user_id', $customer->id)
        ->where('plan_id', $freePlan->id)
        ->latest('id')
        ->firstOrFail();

    expect($subscription->status)->toBe('active');

    $this->assertDatabaseHas('payments', [
        'subscription_id' => $subscription->id,
        'amount' => '0.00',
        'method' => 'cash',
        'status' => 'completed',
    ]);

    expect(Payment::query()
        ->where('subscription_id', $subscription->id)
        ->where('status', 'pending')
        ->exists())->toBeFalse();
});

test('customer cannot claim another free plan after claiming one free plan already', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $firstFreePlan = Plan::factory()->create([
        'price' => 0,
        'duration_days' => 15,
    ]);

    $secondFreePlan = Plan::factory()->create([
        'price' => 0,
        'duration_days' => 30,
    ]);

    Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $firstFreePlan->id,
        'status' => 'active',
        'end_date' => now()->addDays(10)->toDateString(),
    ]);

    $this->actingAs($customer)
        ->from(route('customer.dashboard'))
        ->post(route('customer.payments.checkout'), [
            'plan_id' => $secondFreePlan->id,
            'action' => 'switch',
            'payment_gateway' => 'cash',
        ])
        ->assertRedirect(route('customer.dashboard'))
        ->assertSessionHasErrors([
            'payment_gateway' => 'You can only claim one free plan per account.',
        ]);

    expect(Subscription::query()
        ->where('user_id', $customer->id)
        ->where('plan_id', $secondFreePlan->id)
        ->exists())->toBeFalse();
});

test('customer cannot renew a free plan', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $freePlan = Plan::factory()->create([
        'price' => 0,
        'duration_days' => 20,
    ]);

    Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $freePlan->id,
        'status' => 'active',
        'end_date' => now()->addDays(7)->toDateString(),
    ]);

    $this->actingAs($customer)
        ->from(route('customer.dashboard'))
        ->post(route('customer.payments.checkout'), [
            'plan_id' => $freePlan->id,
            'action' => 'renew',
            'payment_gateway' => 'cash',
        ])
        ->assertRedirect(route('customer.dashboard'))
        ->assertSessionHasErrors([
            'payment_gateway' => 'Free plans cannot be renewed.',
        ]);
});

test('renew payment callback reuses existing plan cycle and removes temporary pending subscription', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create([
        'price' => 95,
        'duration_days' => 30,
    ]);

    $existingSubscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'start_date' => now()->subDays(20)->toDateString(),
        'end_date' => now()->addDays(10)->toDateString(),
    ]);

    $temporaryPendingRenewSubscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(30)->toDateString(),
    ]);

    $expectedRenewedEndDate = Carbon::parse($existingSubscription->end_date)
        ->addDays($plan->duration_days)
        ->toDateString();

    $paymentGatewayService = app(PaymentGatewayService::class);
    $paymentGatewayService->markSubscriptionAsPaid(
        $temporaryPendingRenewSubscription->fresh(),
        'stripe',
        'USD',
        'renew-test-transaction',
        (float) $plan->price,
        ['metadata' => ['action' => 'renew']],
    );

    expect(Subscription::query()
        ->where('user_id', $customer->id)
        ->where('plan_id', $plan->id)
        ->count())->toBe(1);

    $this->assertSoftDeleted('subscriptions', [
        'id' => $temporaryPendingRenewSubscription->id,
    ]);

    expect($existingSubscription->fresh()->end_date->toDateString())->toBe($expectedRenewedEndDate);

    $this->assertDatabaseHas('payments', [
        'subscription_id' => $existingSubscription->id,
        'transaction_id' => 'renew-test-transaction',
        'status' => 'completed',
        'transaction_type' => 'renew',
    ]);
});

test('stripe cancel callback can be handled without auth and removes pending join-plan subscription', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create([
        'price' => 110,
        'duration_days' => 30,
    ]);

    $pendingSubscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    $pendingPayment = Payment::query()->create([
        'subscription_id' => $pendingSubscription->id,
        'amount' => $plan->price,
        'method' => 'stripe',
        'gateway' => 'stripe',
        'status' => 'pending',
        'transaction_type' => 'new',
    ]);

    $cancelToken = app(PaymentGatewayService::class)->buildCheckoutReturnToken(
        'stripe-cancel',
        (int) $pendingSubscription->id,
        (int) $customer->id,
    );

    $this->get(route('customer.payments.stripe.cancel', [
        'subscription' => $pendingSubscription->id,
        'cancel_token' => $cancelToken,
    ]))
        ->assertRedirect(route('customer.dashboard'))
        ->assertSessionHas('warning', 'Stripe checkout was cancelled.');

    $this->assertSoftDeleted('subscriptions', [
        'id' => $pendingSubscription->id,
    ]);

    $this->assertDatabaseHas('payments', [
        'id' => $pendingPayment->id,
        'status' => 'canceled',
    ]);
});

test('sslcommerz fail callback can be handled without auth and removes pending join-plan subscription', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create([
        'price' => 135,
        'duration_days' => 30,
    ]);

    $pendingSubscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    $pendingPayment = Payment::query()->create([
        'subscription_id' => $pendingSubscription->id,
        'amount' => $plan->price,
        'method' => 'sslcommerz',
        'gateway' => 'sslcommerz',
        'status' => 'pending',
        'transaction_type' => 'new',
    ]);

    $callbackToken = app(PaymentGatewayService::class)->buildCheckoutReturnToken(
        'ssl',
        (int) $pendingSubscription->id,
        (int) $customer->id,
    );

    $this->post(route('customer.payments.ssl.fail'), [
        'value_a' => $pendingSubscription->id,
        'value_d' => $callbackToken,
    ])
        ->assertRedirect(route('customer.dashboard'))
        ->assertSessionHas('error', 'SSLCommerz reported payment failure.');

    $this->assertSoftDeleted('subscriptions', [
        'id' => $pendingSubscription->id,
    ]);

    $this->assertDatabaseHas('payments', [
        'id' => $pendingPayment->id,
        'status' => 'canceled',
    ]);
});

test('sslcommerz cancel callback can be handled without auth and removes pending join-plan subscription', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create([
        'price' => 90,
        'duration_days' => 30,
    ]);

    $pendingSubscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    $pendingPayment = Payment::query()->create([
        'subscription_id' => $pendingSubscription->id,
        'amount' => $plan->price,
        'method' => 'sslcommerz',
        'gateway' => 'sslcommerz',
        'status' => 'pending',
        'transaction_type' => 'new',
    ]);

    $callbackToken = app(PaymentGatewayService::class)->buildCheckoutReturnToken(
        'ssl',
        (int) $pendingSubscription->id,
        (int) $customer->id,
    );

    $this->post(route('customer.payments.ssl.cancel'), [
        'value_a' => $pendingSubscription->id,
        'value_d' => $callbackToken,
    ])
        ->assertRedirect(route('customer.dashboard'))
        ->assertSessionHas('warning', 'SSLCommerz checkout was cancelled.');

    $this->assertSoftDeleted('subscriptions', [
        'id' => $pendingSubscription->id,
    ]);

    $this->assertDatabaseHas('payments', [
        'id' => $pendingPayment->id,
        'status' => 'canceled',
    ]);
});
