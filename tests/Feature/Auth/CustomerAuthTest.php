<?php

namespace Tests\Feature\Auth;

use App\Enums\UserType;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        // Seed roles
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_customer_can_view_login_page()
    {
        $response = $this->get('/customer/login');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Guest/Pages/Login'));
    }

    public function test_customer_can_register()
    {
        $plan = Plan::factory()->create();

        $response = $this->post('/customer/register', [
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '+1 202 555 0142',
            'address' => '221B Baker Street',
            'plan_id' => $plan->id,
            'terms' => true,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('customer.registration.success'));

        $user = User::where('email', 'customer@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserType::CUSTOMER, $user->type);
        $this->assertFalse($user->roles()->exists());
        $this->assertTrue(Subscription::query()->where('user_id', $user->id)->where('status', 'pending')->exists());
    }

    public function test_customer_can_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'type' => UserType::CUSTOMER,
        ]);

        $response = $this->post('/customer/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user, 'web');
        $response->assertRedirect(route('customer.dashboard'));
    }
}
