<?php

test('admin payment settings page and request wiring exists', function () {
    $controller = file_get_contents(__DIR__.'/../../app/Http/Controllers/Admin/PaymentSettingsController.php');
    $request = file_get_contents(__DIR__.'/../../app/Http/Requests/Admin/UpdatePaymentSettingsRequest.php');
    $routes = file_get_contents(__DIR__.'/../../routes/admin.php');
    $page = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Business/PaymentSettings.vue');

    expect($controller)
        ->toContain('public function index(): Response')
        ->toContain("inertia('Admin/Business/PaymentSettings'")
        ->toContain('public function update(UpdatePaymentSettingsRequest $request): RedirectResponse');

    expect($request)
        ->toContain("'SSLCOMMERZ_ENABLED' => ['sometimes', 'boolean']")
        ->toContain("'SSLCOMMERZ_STORE_ID'")
        ->toContain("'SSLCOMMERZ_SANDBOX' => ['sometimes', 'boolean']")
        ->toContain("'STRIPE_ENABLED' => ['sometimes', 'boolean']")
        ->toContain("'STRIPE_WEBHOOK_SECRET'");

    expect($routes)
        ->toContain("Route::get('/business/payment-settings'")
        ->toContain("->name('business.payment-settings.index')")
        ->toContain("->name('business.payment-settings.update')");

    expect($page)
        ->toContain('SSLCommerz')
        ->toContain('Stripe')
        ->toContain('SSLCOMMERZ_ENABLED')
        ->toContain('SSLCOMMERZ_STORE_ID')
        ->toContain('STRIPE_ENABLED')
        ->toContain('STRIPE_SECRET_KEY')
        ->toContain('saveSslcommerz')
        ->toContain('saveStripe');
});
