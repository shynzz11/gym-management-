<?php

use App\Enums\UserType;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\MembershipRenewed;
use App\Notifications\WelcomeToIronNest;
use App\Services\PaymentGatewayService;
use App\Services\PaymentService;
use App\Services\SmtpSettingsService;
use App\Services\SubscriptionService;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('creating a customer queues a welcome notification', function () {
    Notification::fake();

    $customer = app(UserService::class)->create([
        'name' => 'New Member',
        'email' => 'new-member@example.test',
        'password' => 'password',
        'type' => UserType::CUSTOMER,
    ]);

    Notification::assertSentTo($customer, WelcomeToIronNest::class);
});

test('approving a renewal payment queues a membership renewal confirmation', function () {
    Notification::fake();

    $customer = User::factory()->create(['type' => UserType::CUSTOMER]);
    $plan = Plan::factory()->create(['duration_days' => 30]);
    $subscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'end_date' => now()->addDays(5)->toDateString(),
    ]);
    $payment = Payment::query()->create([
        'subscription_id' => $subscription->id,
        'amount' => $plan->price,
        'method' => 'cash',
        'status' => 'pending',
        'transaction_type' => 'renew',
    ]);

    app(PaymentService::class)->updatePendingStatus($payment, 'approve');

    Notification::assertSentTo($customer, MembershipRenewed::class);
});

test('paid gateway renewals queue a membership renewal confirmation', function () {
    Notification::fake();

    $customer = User::factory()->create(['type' => UserType::CUSTOMER]);
    $plan = Plan::factory()->create(['duration_days' => 30]);
    $subscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'end_date' => now()->addDays(5)->toDateString(),
    ]);

    app(PaymentGatewayService::class)->markSubscriptionAsPaid(
        $subscription,
        'stripe',
        'USD',
        'renewal-transaction',
        (float) $plan->price,
        ['metadata' => ['action' => 'renew']],
    );

    Notification::assertSentTo($customer, MembershipRenewed::class);
});

test('activating a paid renewal queues a membership renewal confirmation', function () {
    Notification::fake();

    $customer = User::factory()->create(['type' => UserType::CUSTOMER]);
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $customer->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);
    $subscription->payments()->create([
        'amount' => $plan->price,
        'method' => 'stripe',
        'status' => 'completed',
        'transaction_type' => 'renew',
    ]);

    app(SubscriptionService::class)->updateStatus($subscription, 'active');

    Notification::assertSentTo($customer, MembershipRenewed::class);
});

test('saved smtp settings are applied to the mail configuration', function () {
    foreach ([
        'MAIL_HOST' => 'smtp.example.test',
        'MAIL_PORT' => '587',
        'MAIL_USERNAME' => 'smtp-user',
        'MAIL_PASSWORD' => 'smtp-password',
        'MAIL_ENCRYPTION' => 'tls',
        'MAIL_FROM_ADDRESS' => 'members@example.test',
        'MAIL_FROM_NAME' => 'IRON NEST',
    ] as $key => $value) {
        Setting::query()->create(['key' => $key, 'value' => $value]);
    }

    app(SmtpSettingsService::class)->applySavedSettings();

    expect(config('mail.default'))->toBe('smtp')
        ->and(config('mail.mailers.smtp.host'))->toBe('smtp.example.test')
        ->and(config('mail.mailers.smtp.port'))->toBe(587)
        ->and(config('mail.from.address'))->toBe('members@example.test')
        ->and(config('mail.from.name'))->toBe('IRON NEST');
});
