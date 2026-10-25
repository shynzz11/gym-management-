<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\BusinessSetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_legal_settings()
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $admin = User::factory()->create([
            'type' => UserType::ADMIN,
        ]);
        $adminRole = Role::query()->where('slug', 'admin')->first();
        if ($adminRole) {
            $admin->roles()->sync([$adminRole->id]);
        }

        \Illuminate\Support\Facades\Gate::define(\App\Constants\Permissions::LEGAL_MANAGEMENT, function () {
            return true;
        });

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.legal.update'), [
                'privacy_policy' => 'New Privacy Policy Content',
                'terms_and_conditions' => 'New Terms Content',
                'about_us' => 'New About Us Content',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('business_settings', [
            'key' => 'privacy_policy',
            'value' => 'New Privacy Policy Content',
        ]);
    }

    public function test_public_can_view_legal_pages()
    {
        BusinessSetting::create(['key' => 'privacy_policy', 'value' => '# Privacy Policy Header']);

        $response = $this->get('/privacy-policy');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Guest/Legal/Show')
            ->where('title', 'Privacy Policy')
            ->where('content', '# Privacy Policy Header')
        );
    }
}
