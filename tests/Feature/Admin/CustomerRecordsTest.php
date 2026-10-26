<?php

namespace Tests\Feature\Admin;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerRecordsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create([
            'type' => UserType::SUPER_ADMIN,
        ]), 'admin');
    }

    public function test_admin_can_create_a_customer_without_a_password(): void
    {
        $response = $this->post(route('admin.customers.store'), [
            'name' => 'Front Desk Customer',
            'email' => 'front-desk-customer@example.test',
            'phone' => '+1 202 555 0142',
            'address' => '221B Baker Street',
        ]);

        $response->assertRedirect(route('admin.customers.index'));

        $customer = User::query()->where('email', 'front-desk-customer@example.test')->firstOrFail();

        $this->assertSame(UserType::CUSTOMER, $customer->type);
        $this->assertFalse(Hash::check('password', $customer->password));
        $this->assertDatabaseHas('customer_profiles', [
            'user_id' => $customer->id,
            'phone' => '+1 202 555 0142',
            'address' => '221B Baker Street',
        ]);
    }

    public function test_admin_cannot_change_a_customer_password(): void
    {
        $customer = User::factory()->create([
            'type' => UserType::CUSTOMER,
        ]);
        $customer->customerProfile()->create([
            'phone' => '+1 202 555 0199',
            'address' => 'Original address',
        ]);

        $this->put(route('admin.customers.update', $customer), [
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => '+1 202 555 0199',
            'address' => 'Updated address',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseHas('customer_profiles', [
            'user_id' => $customer->id,
            'address' => 'Original address',
        ]);
    }
}
