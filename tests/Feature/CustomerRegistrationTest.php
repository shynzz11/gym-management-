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
});

test('customer registration requires phone and address', function () {
    $plan = Plan::factory()->create();

    $response = $this->post('/customer/register', [
        'name' => 'Customer User',
        'email' => 'customer-required@example.com',
        'plan_id' => $plan->id,
        'terms' => true,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors([
        'phone',
        'address',
    ]);
    $this->assertGuest();
});

test('customer registration requires accepting privacy and terms', function () {
    $plan = Plan::factory()->create();

    $response = $this->post('/customer/register', [
        'name' => 'Customer User',
        'email' => 'customer-terms@example.com',
        'phone' => '+1 202 555 0111',
        'address' => '400 Main St, Miami, FL 33101',
        'plan_id' => $plan->id,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors([
        'terms',
    ]);
    $this->assertGuest();
});

test('customer registration stores phone and address in customer profile', function () {
    $plan = Plan::factory()->create([
        'duration_days' => 30,
    ]);

    $response = $this->post('/customer/register', [
        'name' => 'Customer User',
        'email' => 'customer-profile@example.com',
        'phone' => '+1 202 555 0135',
        'address' => '123 Demo Street, Miami, FL 33101',
        'plan_id' => $plan->id,
        'terms' => true,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('customer.registration.success'));
    $this->assertAuthenticated();

    $customer = User::query()
        ->where('email', 'customer-profile@example.com')
        ->firstOrFail();

    expect($customer->type)->toBe(UserType::CUSTOMER);

    $this->assertDatabaseHas('customer_profiles', [
        'user_id' => $customer->id,
        'phone' => '+1 202 555 0135',
        'address' => '123 Demo Street, Miami, FL 33101',
    ]);

    $subscription = Subscription::query()
        ->where('user_id', $customer->id)
        ->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->status)->toBe('pending')
        ->and($subscription->plan_id)->toBe($plan->id);
});

test('customer registration with free plan activates instantly with cash payment and no pending payment', function () {
    $plan = Plan::factory()->create([
        'price' => 0,
        'duration_days' => 30,
    ]);

    $response = $this->post('/customer/register', [
        'name' => 'Free Plan Customer',
        'email' => 'free-plan-customer@example.com',
        'phone' => '+1 202 555 0148',
        'address' => '201 Free Plan Street, Miami, FL 33101',
        'plan_id' => $plan->id,
        'terms' => true,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('customer.registration.success'))
        ->assertSessionHas('success', 'Registration completed. Your free plan is active now.');

    $customer = User::query()
        ->where('email', 'free-plan-customer@example.com')
        ->firstOrFail();

    $subscription = Subscription::query()
        ->where('user_id', $customer->id)
        ->where('plan_id', $plan->id)
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
