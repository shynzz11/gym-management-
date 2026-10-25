<?php

use App\Constants\Permissions;

test('gym management reports submenu and reports page wiring exist', function () {
    $permissions = Permissions::getAll();
    $routes = file_get_contents(__DIR__.'/../../routes/admin.php');
    $controller = file_get_contents(__DIR__.'/../../app/Http/Controllers/Admin/ReportController.php');
    $page = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Reports/Index.vue');
    $migration = file_get_contents(__DIR__.'/../../database/migrations/2026_02_26_071137_add_view_reports_permission.php');

    expect($permissions[Permissions::SECTION_GYM]['sub_modules'])
        ->toHaveKey(Permissions::VIEW_REPORTS)
        ->and($permissions[Permissions::SECTION_GYM]['sub_modules'][Permissions::VIEW_REPORTS]['route'])
        ->toBe('admin.reports.index')
        ->and($permissions[Permissions::SECTION_GYM]['sub_modules'][Permissions::VIEW_REPORTS]['icon'])
        ->toBe('BarChart2');

    expect($routes)
        ->toContain('use App\Http\Controllers\Admin\ReportController;')
        ->toContain("Route::get('reports', [ReportController::class, 'index'])->name('reports.index');");

    expect($controller)
        ->toContain('Gate::authorize(Permissions::VIEW_REPORTS);')
        ->toContain("return inertia('Admin/Reports/Index', [")
        ->toContain("'expiredMembers' => \$expiredMembers")
        ->toContain("'expiringSoonMembers' => \$expiringSoonMembers")
        ->toContain("'monthlyRenewals' => \$monthlyRenewals")
        ->toContain("'monthlyAddedPlans' => \$monthlyAddedPlans");

    expect($migration)
        ->toContain("\$permissionName = 'view_reports';")
        ->toContain("->whereIn('slug', ['admin', 'super-admin', 'super_admin'])");

    expect($page)
        ->toContain("type ReportTabKey = 'expired' | 'expiringSoon' | 'monthlyRenewals' | 'monthlyAdded';")
        ->toContain("{ key: 'expired' as const, label: 'Expired Members'")
        ->toContain('label: `Expiring Soon (${props.meta?.expiringSoonDayLimit ?? 7} Days)`')
        ->toContain("{ key: 'monthlyRenewals' as const, label: 'This Month Renew'")
        ->toContain("{ key: 'monthlyAdded' as const, label: 'This Month Added Plans'")
        ->toContain("v-if=\"activeTab === 'expired'\"")
        ->toContain("v-else-if=\"activeTab === 'expiringSoon'\"")
        ->toContain("v-else-if=\"activeTab === 'monthlyRenewals'\"");
});
