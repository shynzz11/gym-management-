<?php

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

test('customer can update phone and address from profile page', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $customer->customerProfile()->create([
        'phone' => '+12025550111',
        'address' => 'Old Address',
    ]);

    $this->actingAs($customer)
        ->put(route('customer.profile.update'), [
            'name' => 'Updated Customer',
            'email' => 'updated-customer@example.com',
            'phone' => '+12025550199',
            'address' => '123 Updated Street, Miami, FL 33101',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Profile updated successfully.');

    $this->assertDatabaseHas('users', [
        'id' => $customer->id,
        'name' => 'Updated Customer',
        'email' => 'updated-customer@example.com',
    ]);

    $this->assertDatabaseHas('customer_profiles', [
        'user_id' => $customer->id,
        'phone' => '+12025550199',
        'address' => '123 Updated Street, Miami, FL 33101',
    ]);
});

test('customer profile update validates phone format and address', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $customer->customerProfile()->create([
        'phone' => '+12025550111',
        'address' => 'Old Address',
    ]);

    $this->actingAs($customer)
        ->put(route('customer.profile.update'), [
            'name' => 'Customer One',
            'email' => $customer->email,
            'phone' => '2025550199',
            'address' => '',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors([
            'phone',
            'address',
        ]);
});

test('customer profile update enforces unique phone number', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);
    $otherCustomer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $customer->customerProfile()->create([
        'phone' => '+12025550111',
        'address' => 'Customer Address',
    ]);
    $otherCustomer->customerProfile()->create([
        'phone' => '+12025550222',
        'address' => 'Other Address',
    ]);

    $this->actingAs($customer)
        ->put(route('customer.profile.update'), [
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => '+12025550222',
            'address' => 'Customer Address',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors([
            'phone',
        ]);
});
