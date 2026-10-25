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

    public function test_admin_can_register()
    {
        $response = $this->post('/admin/register', [
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated('admin');
        $response->assertRedirect(route('admin.dashboard'));

        $user = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserType::ADMIN, $user->type);
        $this->assertTrue($user->roles()->where('slug', 'admin')->exists());
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
}
