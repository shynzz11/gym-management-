<?php

test('payment gateway integration wiring exists across config routes and ui', function () {
    $paymentProvider = file_get_contents(__DIR__.'/../../app/Providers/PaymentConfigServiceProvider.php');
    $adminRoutes = file_get_contents(__DIR__.'/../../routes/admin.php');
    $customerRoutes = file_get_contents(__DIR__.'/../../routes/customer.php');
    $webRoutes = file_get_contents(__DIR__.'/../../routes/web.php');
    $bootstrapApp = file_get_contents(__DIR__.'/../../bootstrap/app.php');
    $customerDashboard = file_get_contents(__DIR__.'/../../resources/js/Pages/Customer/Pages/Dashboard.vue');
    $guestRegister = file_get_contents(__DIR__.'/../../resources/js/Pages/Guest/Pages/Register.vue');
    $paymentController = file_get_contents(__DIR__.'/../../app/Http/Controllers/Customer/PaymentController.php');
    $checkoutRequest = file_get_contents(__DIR__.'/../../app/Http/Requests/Customer/StartCheckoutRequest.php');
    $registerRequest = file_get_contents(__DIR__.'/../../app/Http/Requests/Auth/RegisterRequest.php');
    $guestAuthController = file_get_contents(__DIR__.'/../../app/Http/Controllers/Guest/AuthController.php');
    $paymentGatewayService = file_get_contents(__DIR__.'/../../app/Services/PaymentGatewayService.php');
    $paymentModel = file_get_contents(__DIR__.'/../../app/Models/Payment.php');
    $paymentMigration = file_get_contents(__DIR__.'/../../database/migrations/2026_02_25_085052_add_gateway_columns_to_payments_table.php');

    expect($paymentProvider)
        ->toContain("'sslcommerz.store_id'")
        ->toContain("'payment.gateways.sslcommerz.enabled'")
        ->toContain("'payment.gateways.stripe.enabled'")
        ->toContain("'cashier.key'")
        ->toContain("'services.stripe.secret'");

    expect($adminRoutes)
        ->toContain("name('business.payment-settings.index')")
        ->toContain("name('business.payment-settings.update')");

    expect($customerRoutes)
        ->toContain("name('payments.checkout')")
        ->toContain("name('payments.stripe.success')")
        ->toContain("name('payments.ssl.success')");

    expect($webRoutes)
        ->toContain("name('webhooks.stripe')");

    expect($bootstrapApp)
        ->toContain('validateCsrfTokens(except: [')
        ->toContain("'customer/payments/sslcommerz/success'")
        ->toContain("'customer/payments/sslcommerz/fail'")
        ->toContain("'customer/payments/sslcommerz/cancel'")
        ->toContain("'webhooks/stripe'");

    expect($customerDashboard)
        ->toContain("type PaymentGateway = 'sslcommerz' | 'stripe' | 'cash';")
        ->toContain('subscribedPlanIds?: number[];')
        ->toContain('const subscribedPlanIdSet = computed(() => {')
        ->toContain('const systemCurrencyCode = computed(() => {')
        ->toContain('const joinablePlans = computed(() => {')
        ->toContain('const filteredJoinablePlans = computed(() => {')
        ->toContain("const effectivePlanAction = computed<'renew' | 'switch'>(() => {")
        ->toContain('const isFreeCheckoutPlan = computed(() => checkoutAmountBdt.value <= 0);')
        ->toContain('const enabledPaymentGateways = computed<PaymentGateway[]>(() => {')
        ->toContain("if (isFreeCheckoutPlan.value) {\n        return ['cash'];\n    }")
        ->toContain('checkoutDisplayAmount')
        ->toContain('action: effectivePlanAction.value')
        ->toContain('submitPlanActionLabel')
        ->toContain("{{ __('Price') }}")
        ->toContain("{{ __('Currency') }}")
        ->toContain('payment_gateway: data.payment_gateway')
        ->toContain('v-for="plan in filteredJoinablePlans"')
        ->toContain('v-for="gateway in enabledPaymentGateways"')
        ->toContain('.post(customer.payments.checkout.url()')
        ->not->toContain('<PaymentSelectionModal')
        ->not->toContain('checkoutAmountUsd');

    expect($guestRegister)
        ->toContain("type PaymentGateway = 'sslcommerz' | 'stripe' | 'cash';")
        ->toContain("import PaymentSelectionModal from '@/Components/Common/PaymentSelectionModal.vue';")
        ->toContain('const availablePaymentGateways = computed<PaymentGateway[]>(() => {')
        ->toContain('const selectedPlanAmount = computed(() => {')
        ->toContain("if (selectedPlanAmount.value <= 0) {\n        return ['cash'];\n    }")
        ->toContain('const systemCurrencyCode = computed(() => {')
        ->toContain("payment_gateway: ''")
        ->toContain('form.country_iso = phoneCountryIso.value')
        ->toContain(':available-gateways="availablePaymentGateways"')
        ->toContain(':currency-code="systemCurrencyCode"')
        ->toContain('<PaymentSelectionModal');

    expect($paymentController)
        ->toContain('public function checkout(StartCheckoutRequest $request): Response')
        ->toContain('$hasPlanHistory = $user->subscriptions()')
        ->toContain("\$effectiveAction = \$hasPlanHistory ? 'renew' : \$requestedAction;")
        ->toContain('?? $this->paymentGatewayService->resolveGatewayBySystemCurrency())')
        ->toContain('public function stripeWebhook(Request $request): JsonResponse')
        ->toContain("if (\$gateway === 'cash') {")
        ->toContain('Cash payment is pending admin approval.')
        ->toContain('Stripe webhook secret is not configured.')
        ->toContain('return Inertia::location($checkoutUrl);');

    expect($checkoutRequest)
        ->toContain("'in:sslcommerz,stripe,cash'")
        ->toContain("\$value !== 'cash'")
        ->toContain('isGatewayAvailableForSystemCurrency');

    expect($registerRequest)
        ->toContain("'in:sslcommerz,stripe,cash'")
        ->toContain('isGatewayAvailableForSystemCurrency');

    expect($guestAuthController)
        ->toContain("'paymentOptions' => \$this->paymentGatewayService->getCustomerPaymentOptions()")
        ->toContain('?? $this->paymentGatewayService->resolveGatewayBySystemCurrency())')
        ->toContain("if (\$selectedGateway === 'cash') {")
        ->toContain('Cash payment is pending admin approval.');

    expect($paymentGatewayService)
        ->toContain('public function resolveSystemCurrencyCode(): string')
        ->toContain('public function isGatewayAvailableForSystemCurrency(string $gateway): bool')
        ->toContain('public function getCustomerPaymentOptions(): array')
        ->toContain('resolveRenewalTargetSubscription')
        ->toContain('applyRenewalCycle')
        ->toContain('paymentSupportsTransactionType')
        ->toContain('line_items[0][price_data][currency]')
        ->not->toContain('convertBdtToUsd');

    expect($paymentModel)
        ->toContain("'gateway'")
        ->toContain("'currency'")
        ->toContain("'transaction_id'")
        ->toContain("'gateway_payload'");

    expect($paymentMigration)
        ->toContain("\$table->string('gateway')->nullable()")
        ->toContain("\$table->string('currency', 10)->nullable()")
        ->toContain("\$table->string('transaction_id')->nullable()")
        ->toContain("\$table->json('gateway_payload')->nullable()");
});
