<?php

test('all page titles follow page name plus dynamic business name', function () {
    $appBootstrap = file_get_contents(__DIR__.'/../../resources/js/app.ts');
    $ssrBootstrap = file_get_contents(__DIR__.'/../../resources/js/ssr.ts');
    $inertiaMiddleware = file_get_contents(__DIR__.'/../../app/Http/Middleware/HandleInertiaRequests.php');
    $appServiceProvider = file_get_contents(__DIR__.'/../../app/Providers/AppServiceProvider.php');
    $brandingPage = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Business/Branding.vue');
    $adminDashboardPage = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Pages/Dashboard.vue');
    $adminLoginPage = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Auth/Login.vue');

    expect($appBootstrap)
        ->toContain('let appName = getInitialPageName() || fallbackAppName;')
        ->toContain('const resolveAppNameFromPage = (page?: InertiaPageWithName): string | null => {')
        ->toContain('const businessName = resolveAppName(page?.props?.branding?.business_settings?.business_name);')
        ->toContain('title: (title) => (title ? `${title} - ${appName}` : appName),')
        ->toContain("router.on('navigate', (event) => {");

    expect($ssrBootstrap)
        ->toContain('const resolveAppNameFromProps = (props: InertiaPageProps): string | null => {')
        ->toContain('const businessName = resolveAppName(props.branding?.business_settings?.business_name);')
        ->toContain('const appName = resolveAppNameFromProps(page.props as InertiaPageProps)');

    expect($inertiaMiddleware)
        ->toContain('$businessSettings = \App\Models\BusinessSetting::all()->pluck(\'value\', \'key\')->toArray();')
        ->toContain('$businessName = trim((string) ($businessSettings[\'business_name\'] ?? \'\'));')
        ->toContain("'name' => \$businessName,");

    expect($appServiceProvider)
        ->toContain('$configuredBusinessName = trim((string) business_config(\'business_name\', \'\'));')
        ->toContain('$appName = $configuredBusinessName !== \'\' ? $configuredBusinessName : \'GymOS\';')
        ->toContain('config([\'app.name\' => $appName]);');

    expect($brandingPage)
        ->toContain('const page = usePage<{ name?: unknown }>();')
        ->toContain('business_name: props.settings.business_name || resolveDefaultBusinessName(),')
        ->toContain('form.business_name = newSettings.business_name || resolveDefaultBusinessName();')
        ->not->toContain("business_name: props.settings.business_name || 'GymOS',");

    expect($adminDashboardPage)
        ->toContain("const businessName = computed(() => businessSettings.value.business_name || page.props.name || 'GymOS');")
        ->toContain('<Head :title="__(\'Command Center\')" />')
        ->toContain("{{ businessName }} {{ __('Command Center') }}")
        ->not->toContain("__('GymMaster Command Center')");

    expect($adminLoginPage)
        ->toContain('<Head :title="__(\'Admin Login\')" />')
        ->not->toContain('`${appName} | Admin Login`');
});
