<?php

use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\MinimalSeeder;

test('super admin can be created with strong credentials', function () {
    $this->artisan('gym:create-super-admin')
        ->expectsQuestion('Name', 'Production Super Admin')
        ->expectsQuestion('Email', 'superadmin@example.com')
        ->expectsQuestion('Password (min 12 chars, mixed case, number, symbol)', 'Gym-Secure-Password-2026!')
        ->expectsOutput('Super admin created: superadmin@example.com')
        ->assertExitCode(0);

    $user = User::query()->where('email', 'superadmin@example.com')->firstOrFail();

    expect($user->type)->toBe(UserType::SUPER_ADMIN)
        ->and($user->adminProfile)->not->toBeNull()
        ->and(password_verify('Gym-Secure-Password-2026!', $user->password))->toBeTrue();
});

test('super admin creation refuses to add another super admin', function () {
    User::factory()->create(['type' => UserType::SUPER_ADMIN]);

    $this->artisan('gym:create-super-admin')
        ->expectsOutput('A super admin already exists. Aborting.')
        ->assertExitCode(1);

    expect(User::query()->where('type', UserType::SUPER_ADMIN)->count())->toBe(1);
});

test('super admin creation rejects a weak password', function () {
    $this->artisan('gym:create-super-admin')
        ->expectsQuestion('Name', 'Production Super Admin')
        ->expectsQuestion('Email', 'superadmin@example.com')
        ->expectsQuestion('Password (min 12 chars, mixed case, number, symbol)', 'weak')
        ->expectsOutputToContain('The password field must be at least 12 characters.')
        ->assertExitCode(1);

    expect(User::query()->where('email', 'superadmin@example.com')->exists())->toBeFalse();
});

test('destructive demo seeders are blocked in production before touching the database', function () {
    app()->detectEnvironment(fn () => 'production');

    foreach ([MinimalSeeder::class, DemoSeeder::class, AdminUserSeeder::class] as $seeder) {
        expect(fn () => app($seeder)->run())
            ->toThrow(RuntimeException::class);
    }
});
