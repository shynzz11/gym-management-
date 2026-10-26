<?php

use App\Enums\UserType;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Services\BusinessSettingService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('it uses system default favicon when branding favicon is not configured', function () {
    $defaultFaviconUrl = 'https://api.dicebear.com/7.x/initials/svg?seed=N';

    $this->get(route('admin.login'))
        ->assertOk()
        ->assertSee('rel="icon" href="'.$defaultFaviconUrl.'"', false);
});

test('it uses branding favicon when a custom url is configured', function () {
    $faviconUrl = 'https://example.com/custom-favicon.png';

    BusinessSetting::create([
        'key' => 'favicon_url',
        'value' => $faviconUrl,
    ]);

    $this->get(route('admin.login'))
        ->assertOk()
        ->assertSee('rel="icon" href="'.$faviconUrl.'"', false);
});

test('branding update stores favicon url text value', function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);

    $admin = User::factory()->create([
        'type' => UserType::SUPER_ADMIN,
        'status' => 'active',
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.business.update'), [
            'favicon_url' => '',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('business_settings', [
        'key' => 'favicon_url',
        'value' => '',
    ]);
});

test('branding logo upload stores a public path and formats it for the browser', function () {
    Storage::fake('public');

    app(BusinessSettingService::class)->updateBranding([], [
        'logo_url' => UploadedFile::fake()->create('gym-logo.png', 100, 'image/png'),
    ]);

    $storedPath = BusinessSetting::query()->where('key', 'logo_url')->value('value');

    expect($storedPath)->toStartWith('branding/');
    Storage::disk('public')->assertExists($storedPath);
    expect(app(BusinessSettingService::class)->getSettings(['logo_url'], true)->get('logo_url'))
        ->toBe('/storage/'.$storedPath);
});
