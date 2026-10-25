<?php

use App\Enums\UserType;
use App\Mail\TestSmtpMail;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

test('super admin can update email settings', function () {
    $superAdmin = User::factory()->create([
        'type' => UserType::SUPER_ADMIN,
        'status' => 'active',
    ]);

    $payload = [
        'MAIL_HOST' => 'smtp.example.com',
        'MAIL_PORT' => 587,
        'MAIL_USERNAME' => 'smtp-user',
        'MAIL_PASSWORD' => 'smtp-password',
        'MAIL_ENCRYPTION' => 'tls',
        'MAIL_FROM_ADDRESS' => 'noreply@example.com',
        'MAIL_FROM_NAME' => 'Example App',
    ];

    $this->actingAs($superAdmin, 'admin')
        ->post(route('admin.business.email.update'), $payload)
        ->assertRedirect();

    foreach ($payload as $key => $value) {
        $this->assertDatabaseHas('settings', [
            'key' => $key,
            'value' => (string) $value,
        ]);
    }
});

test('super admin can send test email from email settings page', function () {
    Mail::fake();

    $superAdmin = User::factory()->create([
        'type' => UserType::SUPER_ADMIN,
        'status' => 'active',
    ]);

    $payload = [
        'MAIL_HOST' => 'smtp.example.com',
        'MAIL_PORT' => 587,
        'MAIL_USERNAME' => 'smtp-user',
        'MAIL_PASSWORD' => 'smtp-password',
        'MAIL_ENCRYPTION' => 'tls',
        'MAIL_FROM_ADDRESS' => 'noreply@example.com',
        'MAIL_FROM_NAME' => 'Example App',
        'test_email' => 'tester@example.com',
    ];

    $this->actingAs($superAdmin, 'admin')
        ->post(route('admin.business.email.test'), $payload)
        ->assertRedirect();

    Mail::assertSent(TestSmtpMail::class, function (TestSmtpMail $mail) {
        return $mail->hasTo('tester@example.com');
    });
});

test('test email endpoint validates recipient email address', function () {
    Mail::fake();

    $superAdmin = User::factory()->create([
        'type' => UserType::SUPER_ADMIN,
        'status' => 'active',
    ]);

    $payload = [
        'MAIL_HOST' => 'smtp.example.com',
        'MAIL_PORT' => 587,
        'MAIL_USERNAME' => 'smtp-user',
        'MAIL_PASSWORD' => 'smtp-password',
        'MAIL_ENCRYPTION' => 'tls',
        'MAIL_FROM_ADDRESS' => 'noreply@example.com',
        'MAIL_FROM_NAME' => 'Example App',
        'test_email' => 'invalid-email',
    ];

    $this->actingAs($superAdmin, 'admin')
        ->post(route('admin.business.email.test'), $payload)
        ->assertSessionHasErrors(['test_email']);

    Mail::assertNothingSent();
});

test('regular admin cannot send test email without permission', function () {
    $admin = User::factory()->create([
        'type' => UserType::ADMIN,
        'status' => 'active',
    ]);

    $payload = [
        'MAIL_HOST' => 'smtp.example.com',
        'MAIL_PORT' => 587,
        'MAIL_USERNAME' => 'smtp-user',
        'MAIL_PASSWORD' => 'smtp-password',
        'MAIL_ENCRYPTION' => 'tls',
        'MAIL_FROM_ADDRESS' => 'noreply@example.com',
        'MAIL_FROM_NAME' => 'Example App',
        'test_email' => 'tester@example.com',
    ];

    $this->actingAs($admin, 'admin')
        ->post(route('admin.business.email.test'), $payload)
        ->assertForbidden();
});
