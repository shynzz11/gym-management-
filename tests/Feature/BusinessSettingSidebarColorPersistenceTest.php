<?php

use App\Models\BusinessSetting;
use App\Services\BusinessSettingService;

test('branding updates do not auto-copy rail color into main sidebar colors', function () {
    $service = app(BusinessSettingService::class);

    $service->updateBranding([
        'sidebar_rail_bg' => '#22c55e',
        'sidebar_rail_bg_dark' => '#15803d',
    ]);

    expect(BusinessSetting::query()->where('key', 'admin_sidebar_bg')->exists())->toBeFalse()
        ->and(BusinessSetting::query()->where('key', 'admin_sidebar_bg_dark')->exists())->toBeFalse();
});
