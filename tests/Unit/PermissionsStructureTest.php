<?php

use App\Constants\Permissions;

test('gym section is first and dashboard is first gym submenu', function () {
    $permissions = Permissions::getAll();
    $permissionKeys = array_keys($permissions);

    expect($permissionKeys[0])->toBe(Permissions::SECTION_GYM)
        ->and($permissions)->not->toHaveKey(Permissions::SECTION_SYSTEM)
        ->and($permissions[Permissions::SECTION_GYM]['label'])->toBe('gym_management')
        ->and(array_key_first($permissions[Permissions::SECTION_GYM]['sub_modules']))->toBe(Permissions::DASHBOARD_VIEW)
        ->and($permissions[Permissions::SECTION_GYM]['sub_modules'][Permissions::DASHBOARD_VIEW]['label'])->toBe('dashboard')
        ->and($permissions[Permissions::SECTION_GYM]['sub_modules'][Permissions::DASHBOARD_VIEW]['route'])->toBe('admin.dashboard');
});

test('activity log is grouped under business settings section', function () {
    $permissions = Permissions::getAll();

    expect($permissions[Permissions::SECTION_BUSINESS]['sub_modules'])
        ->toHaveKey(Permissions::ACTIVITY_LOG)
        ->and($permissions[Permissions::SECTION_BUSINESS]['sub_modules'][Permissions::ACTIVITY_LOG]['route'])
        ->toBe('admin.activity_logs.index');
});
