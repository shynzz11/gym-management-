<?php

test('business settings defaults are seeded through migration and controller fallbacks match', function () {
    $migration = file_get_contents(__DIR__.'/../../database/migrations/2026_02_25_094304_seed_default_business_logic_settings.php');
    $currencyMigration = file_get_contents(__DIR__.'/../../database/migrations/2026_10_25_000001_replace_default_taka_with_rupee.php');
    $controller = file_get_contents(__DIR__.'/../../app/Http/Controllers/Admin/BusinessLogicController.php');
    $page = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Business/BusinessLogic.vue');
    $currencyComposable = file_get_contents(__DIR__.'/../../resources/js/Composables/useCurrency.ts');

    expect($migration)
        ->toContain("'currency_symbol' => '₹'")
        ->toContain("'timezone' => 'Asia/Kolkata'")
        ->toContain("'country' => 'India'");

    expect($currencyMigration)
        ->toContain("->where('value', '৳')")
        ->toContain("'value' => '₹'");

    expect($controller)
        ->toContain("'currency_symbol' => \$settings->get('currency_symbol', '₹')")
        ->toContain("'timezone' => \$settings->get('timezone', 'Asia/Kolkata')")
        ->toContain("'country' => \$settings->get('country', 'India')");

    expect($page)
        ->toContain("{ value: '₹'")
        ->not->toContain("{ value: '৳'")
        ->toContain('visibleCurrencies')
        ->toContain('visibleTimezones')
        ->toContain('visibleCountries');

    expect($currencyComposable)
        ->toContain("const DEFAULT_CURRENCY_SYMBOL = '₹'")
        ->toContain('currencyDisplay: \'narrowSymbol\'')
        ->toContain('return resolveCurrencySymbol(sanitizedValue)');
});
