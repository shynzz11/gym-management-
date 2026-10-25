<?php

use App\Enums\UserType;
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

test('subscriptions index renders customers, plans, and subscriptions data', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    User::factory()->create([
        'type' => UserType::ADMIN,
    ]);

    $plan = Plan::factory()->create([
        'name' => 'Starter',
    ]);

    Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.subscriptions.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Subscriptions/Index')
            ->has('subscriptions.data', 1)
            ->where('subscriptions.current_page', 1)
            ->has('plans')
            ->has('users', 1)
            ->has('activeSubscriptionAssignments')
            ->where('users.0.id', $customer->id)
            ->where('plans', fn ($plans) => collect($plans)->contains(fn ($item) => $item['id'] === $plan->id))
        );
});

test('subscriptions index supports search and pagination', function () {
    $targetCustomer = User::factory()->create([
        'type' => UserType::CUSTOMER,
        'name' => 'Target Member',
        'email' => 'target@example.test',
    ]);

    $targetPlan = Plan::factory()->create([
        'name' => 'Target Plan',
    ]);

    Subscription::factory()->create([
        'user_id' => $targetCustomer->id,
        'plan_id' => $targetPlan->id,
        'status' => 'active',
    ]);

    Subscription::factory()->count(10)->create();

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.subscriptions.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Subscriptions/Index')
            ->has('subscriptions.data', 10)
            ->where('subscriptions.last_page', 2)
        );

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.subscriptions.index', [
            'search' => 'target@example.test',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Subscriptions/Index')
            ->where('filters.search', 'target@example.test')
            ->has('subscriptions.data', 1)
            ->where('subscriptions.data.0.user.id', $targetCustomer->id)
            ->where('subscriptions.data.0.plan.id', $targetPlan->id)
        );
});

test('subscriptions index supports preselecting customer for assign plan flow', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.subscriptions.index', [
            'assign_customer_id' => $customer->id,
            'open_assign_modal' => 1,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Subscriptions/Index')
            ->where('preselectedCustomerId', $customer->id)
            ->where('openAssignModal', true)
        );
});

test('subscriptions index marks active plan as pending-payment locked when same plan has pending payment request', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create();

    $activeSubscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'end_date' => now()->addDays(7)->toDateString(),
    ]);

    $pendingSubscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    $pendingSubscription->payments()->create([
        'amount' => $plan->price,
        'method' => 'cash',
        'status' => 'pending',
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.subscriptions.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Subscriptions/Index')
            ->where('subscriptions.data', fn ($subscriptions) => collect($subscriptions)->contains(
                fn ($subscription) => (int) ($subscription['id'] ?? 0) === (int) $activeSubscription->id
                    && (bool) ($subscription['has_pending_payment_request'] ?? false) === true
            ))
        );
});

test('can create manual active subscription and auto-create payment', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create([
        'price' => 99.00,
        'duration_days' => 45,
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->post(route('admin.subscriptions.store'), [
            'user_id' => $customer->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Subscription created successfully.');

    $subscription = Subscription::query()
        ->where('user_id', $customer->id)
        ->where('plan_id', $plan->id)
        ->firstOrFail();

    expect($subscription->status)->toBe('active');

    $this->assertDatabaseHas('payments', [
        'subscription_id' => $subscription->id,
        'amount' => '99.00',
        'method' => 'manual',
        'status' => 'completed',
    ]);
});

test('selected start date sets the subscription cycle dates when assigning a plan', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create([
        'duration_days' => 45,
    ]);
    $startDate = now()->subDays(10)->toDateString();

    $this->actingAs($this->superAdmin, 'admin')
        ->post(route('admin.subscriptions.store'), [
            'user_id' => $customer->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'start_date' => $startDate,
        ])
        ->assertRedirect();

    $subscription = Subscription::query()
        ->where('user_id', $customer->id)
        ->where('plan_id', $plan->id)
        ->firstOrFail();

    expect($subscription->start_date->toDateString())->toBe($startDate);
    expect($subscription->end_date->toDateString())->toBe(now()->parse($startDate)->addDays(45)->toDateString());
    expect($subscription->status)->toBe('active');
});

test('selected start date resets an existing active subscription renewal cycle', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create([
        'duration_days' => 30,
    ]);
    $existingSubscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'start_date' => now()->subDays(30)->toDateString(),
        'end_date' => now()->addDays(15)->toDateString(),
    ]);
    $startDate = now()->addDays(5)->toDateString();

    $this->actingAs($this->superAdmin, 'admin')
        ->post(route('admin.subscriptions.store'), [
            'user_id' => $customer->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'start_date' => $startDate,
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Subscription scheduled to activate on its start date.');

    $existingSubscription->refresh();

    expect($existingSubscription->start_date->toDateString())->toBe($startDate);
    expect($existingSubscription->end_date->toDateString())->toBe(now()->parse($startDate)->addDays(30)->toDateString());
    expect($existingSubscription->status)->toBe('pending');
    expect($existingSubscription->scheduled_activation)->toBeTrue();

    $this->assertDatabaseHas('payments', [
        'subscription_id' => $existingSubscription->id,
        'status' => 'completed',
        'transaction_type' => 'renew',
    ]);
});

test('scheduled activation activates only subscriptions approved for scheduled activation', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);
    $plan = Plan::factory()->create();
    $scheduledSubscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
        'start_date' => now()->toDateString(),
        'scheduled_activation' => true,
    ]);
    $ordinaryPendingSubscription = Subscription::factory()->create([
        'status' => 'pending',
        'start_date' => now()->toDateString(),
        'scheduled_activation' => false,
    ]);

    \Illuminate\Support\Facades\Artisan::call('membership:activate-scheduled');

    $this->assertDatabaseHas('subscriptions', [
        'id' => $scheduledSubscription->id,
        'status' => 'active',
        'scheduled_activation' => false,
    ]);
    $this->assertDatabaseHas('subscriptions', [
        'id' => $ordinaryPendingSubscription->id,
        'status' => 'pending',
        'scheduled_activation' => false,
    ]);
});

test('approving a future-dated pending subscription schedules it without granting early access', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);
    $plan = Plan::factory()->create();
    $startDate = now()->addDays(3)->toDateString();

    $this->actingAs($this->superAdmin, 'admin')
        ->post(route('admin.subscriptions.store'), [
            'user_id' => $customer->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'start_date' => $startDate,
        ])
        ->assertRedirect();

    $subscription = Subscription::query()
        ->where('user_id', $customer->id)
        ->where('plan_id', $plan->id)
        ->firstOrFail();

    expect($subscription->status)->toBe('pending');
    expect($subscription->scheduled_activation)->toBeFalse();

    $this->actingAs($this->superAdmin, 'admin')
        ->patch(route('admin.subscriptions.status', $subscription), [
            'status' => 'active',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Subscription approved and scheduled to activate on its start date.');

    expect($subscription->refresh()->status)->toBe('pending');
    expect($subscription->scheduled_activation)->toBeTrue();
    expect($subscription->payments()->where('status', 'completed')->exists())->toBeTrue();
});

test('can approve pending subscription from subscriptions status endpoint', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->patch(route('admin.subscriptions.status', $subscription), [
            'status' => 'active',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Subscription status updated successfully.');

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'status' => 'active',
    ]);
});

test('renews existing active subscription for the same plan instead of creating duplicate', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $plan = Plan::factory()->create([
        'price' => 75.00,
        'duration_days' => 30,
    ]);

    $existingSubscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'end_date' => now()->addDays(7)->toDateString(),
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->post(route('admin.subscriptions.store'), [
            'user_id' => $customer->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Subscription renewed successfully.');

    $existingSubscription->refresh();

    expect(
        Subscription::query()
            ->where('user_id', $customer->id)
            ->where('plan_id', $plan->id)
            ->count()
    )->toBe(1);

    expect($existingSubscription->status)->toBe('active');
    expect($existingSubscription->end_date->toDateString())->toBe(now()->addDays(37)->toDateString());

    $this->assertDatabaseHas('payments', [
        'subscription_id' => $existingSubscription->id,
        'amount' => '75.00',
        'method' => 'manual',
        'status' => 'completed',
    ]);
});

test('can create multiple active subscriptions for different plans', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $basicPlan = Plan::factory()->create([
        'name' => 'Monthly Basic',
    ]);

    $addonPlan = Plan::factory()->create([
        'name' => 'Sauna Add-on',
    ]);

    Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $basicPlan->id,
        'status' => 'active',
        'end_date' => now()->addDays(7)->toDateString(),
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->post(route('admin.subscriptions.store'), [
            'user_id' => $customer->id,
            'plan_id' => $addonPlan->id,
            'status' => 'active',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Subscription created successfully.');

    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $customer->id,
        'plan_id' => $addonPlan->id,
        'status' => 'active',
    ]);
});
