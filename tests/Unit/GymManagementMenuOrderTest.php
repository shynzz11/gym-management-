<?php

use App\Constants\Permissions;

it('places customer list and subscriptions immediately after dashboard in gym management menu', function () {
    $permissions = Permissions::getAll();
    $gymSubModules = $permissions[Permissions::SECTION_GYM]['sub_modules'] ?? [];
    $gymSubModuleKeys = array_keys($gymSubModules);

    expect(count($gymSubModuleKeys))
        ->toBeGreaterThanOrEqual(5)
        ->and($gymSubModules)
        ->toHaveKey(Permissions::DASHBOARD_VIEW)
        ->toHaveKey(Permissions::MEMBER_DIRECTORY)
        ->toHaveKey(Permissions::MANAGE_SUBSCRIPTIONS)
        ->toHaveKey(Permissions::VIEW_PENDING_PAYMENTS)
        ->toHaveKey(Permissions::VIEW_PAYMENTS)
        ->and($gymSubModuleKeys[0])->toBe(Permissions::DASHBOARD_VIEW)
        ->and($gymSubModuleKeys[1])->toBe(Permissions::MEMBER_DIRECTORY)
        ->and($gymSubModuleKeys[2])->toBe(Permissions::MANAGE_SUBSCRIPTIONS)
        ->and($gymSubModuleKeys[3])->toBe(Permissions::VIEW_PENDING_PAYMENTS)
        ->and($gymSubModuleKeys[4])->toBe(Permissions::VIEW_PAYMENTS);
});
