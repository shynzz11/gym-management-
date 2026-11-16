<?php

use App\Enums\UserType;
use App\Models\BusinessSetting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('legal content is sanitised before it is stored', function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
    $superAdmin = User::factory()->create(['type' => UserType::SUPER_ADMIN, 'status' => 'active']);

    $this->actingAs($superAdmin, 'admin')
        ->post(route('admin.legal.update'), [
            'privacy_policy' => '<p>Policy</p><script>alert(1)</script><img src=x onerror=alert(1)>',
            'terms_and_conditions' => '<p onclick="alert(1)">Terms</p>',
            'about_us' => null,
        ])
        ->assertRedirect();

    expect(BusinessSetting::query()->where('key', 'privacy_policy')->value('value'))->toBe('<p>Policy</p>')
        ->and(BusinessSetting::query()->where('key', 'terms_and_conditions')->value('value'))->toBe('<p>Terms</p>');
});

test('public legal page sanitises content stored before sanitisation existed', function () {
    BusinessSetting::query()->updateOrCreate(
        ['key' => 'privacy_policy'],
        ['value' => '<h2>Policy</h2><img src=x onerror=alert(document.cookie)>'],
    );

    $this->get(route('legal.privacy'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Guest/Legal/Show')
            ->where('content', '<h2>Policy</h2>')
        );
});
