<?php

test('customer dashboard plan facilities list shows only selected plan facilities', function () {
    $dashboardPage = file_get_contents(__DIR__.'/../../resources/js/Pages/Customer/Pages/Dashboard.vue');

    expect($dashboardPage)
        ->toContain("type PaymentGateway = 'sslcommerz' | 'stripe' | 'cash';")
        ->toContain('subscribedPlanIds?: number[];')
        ->toContain('pendingRenewPlanIds?: number[];')
        ->toContain('const subscribedPlanIdSet = computed(() => {')
        ->toContain('const pendingRenewPlanIdSet = computed(() => {')
        ->toContain('const joinablePlans = computed(() => {')
        ->toContain('const filteredJoinablePlans = computed(() => {')
        ->toContain("const effectivePlanAction = computed<'renew' | 'switch'>(() => {")
        ->toContain('const enabledPaymentGateways = computed<PaymentGateway[]>(() => {')
        ->toContain('const selectedPlanFacilities = computed(() => {')
        ->toContain('const hasSelectedPlanPendingRenewalRequest = computed(() => {')
        ->toContain('const submitPlanActionLabel = computed(() => {')
        ->toContain('action: effectivePlanAction.value')
        ->toContain('const getPlanTimeLabel = (subscription: any): string => {')
        ->toContain("return 'Any time';")
        ->toContain("{{ __('Plan Time') }}: {{ getPlanTimeLabel(selectedSubscription) }}")
        ->toContain("@click=\"openPlanActionModal('switch')\"")
        ->toContain('const hasSelectedFreePlan = computed(() => {')
        ->toContain(':disabled="hasSelectedPlanPendingRenewalRequest || hasSelectedFreePlan"')
        ->toContain("? __('Free Plan')")
        ->toContain("{{ __('Free plans are one-time only and cannot be renewed.') }}")
        ->toContain("{{ __('Join Plan') }}")
        ->toContain("{{ __('Join Plan & Pay') }}")
        ->toContain("{{ planActionType === 'renew' ? __('Renew Your Plan') : __('Join New Plan') }}")
        ->toContain('class="relative flex h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-3xl')
        ->toContain("{{ __('Selected Plan') }}")
        ->toContain('selectedJoinPlanLabel')
        ->toContain('v-model="joinPlanSearchQuery"')
        ->toContain("__('Search plan by name, price, or duration')")
        ->toContain('v-for="plan in filteredJoinablePlans"')
        ->toContain('v-for="gateway in enabledPaymentGateways"')
        ->toContain("{{ __('Plan Time') }}")
        ->toContain('v-if="selectedPlanFacilities.length > 0"')
        ->toContain('<PlanFeatureList :features="selectedPlanFacilities" :show-only-included="true" />');

    expect($dashboardPage)
        ->not->toContain("{{ __('Switch Plan') }}")
        ->not->toContain("{{ planActionType === 'renew' ? __('Renew Your Plan') : __('Switch to New Plan') }}")
        ->not->toContain('<PaymentSelectionModal');
});
