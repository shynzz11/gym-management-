<?php

namespace Tests\Feature\Auth;

use App\Enums\UserType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        // Seed roles
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_admin_can_view_login_page()
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Auth/Login'));
    }

    public function test_admin_registration_is_not_public()
    {
        $this->get('/admin/register')->assertNotFound();
        $this->post('/admin/register')->assertNotFound();
    }

    public function test_customer_portal_routes_are_not_public()
    {
        $this->get('/customer/login')->assertNotFound();
        $this->post('/customer/login')->assertNotFound();
        $this->get('/customer/register')->assertNotFound();
        $this->post('/customer/register')->assertNotFound();
        $this->get('/customer/dashboard')->assertNotFound();
    }

    public function test_admin_can_login()
    {
        $role = Role::where('slug', 'admin')->firstOrFail();
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'type' => UserType::ADMIN,
        ]);
        $user->roles()->sync([$role->id]);

        $response = $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user, 'admin');
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_customer_cannot_log_in_through_the_admin_guard()
    {
        $customer = User::factory()->create([
            'password' => bcrypt('password'),
            'type' => UserType::CUSTOMER,
        ]);

        $response = $this->post('/admin/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $this->assertGuest('admin');
        $response->assertSessionHasErrors('email');
    }
}
