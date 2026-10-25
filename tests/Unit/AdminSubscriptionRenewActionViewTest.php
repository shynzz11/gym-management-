<?php

test('admin subscriptions table shows approve for pending and renew for active', function () {
    $subscriptionsIndexPage = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Subscriptions/Index.vue');

    expect($subscriptionsIndexPage)
        ->toContain("import ConfirmationModal from '@/Components/Common/ConfirmationModal.vue';")
        ->toContain('const approveSubscription = (subscriptionId: number) => {')
        ->toContain('const renewSubscription = (subscription: any) => {')
        ->toContain("const openActionConfirm = (action: 'approve' | 'renew' | 'deny', subscription: any) => {")
        ->toContain('const executeConfirmedAction = () => {')
        ->toContain('const hasPendingPaymentRequest = (subscription: any): boolean => {')
        ->toContain('const renewActionTooltip = (subscription: any): string => {')
        ->toContain("v-if=\"sub.status === 'pending'\"")
        ->toContain('@click="openActionConfirm(\'approve\', sub)"')
        ->toContain("{{ processingActionSubscriptionId === sub.id && pendingActionType === 'approve' ? __('Approving...') : __('Approve') }}")
        ->toContain("v-else-if=\"['active', 'expired'].includes(sub.status)\"")
        ->toContain('<AppTooltip v-else-if="[\'active\', \'expired\'].includes(sub.status)" :text="renewActionTooltip(sub)">')
        ->toContain('@click="openActionConfirm(\'renew\', sub)"')
        ->toContain(':disabled="processingActionSubscriptionId === sub.id || hasPendingPaymentRequest(sub)"')
        ->toContain('hasPendingPaymentRequest(sub)')
        ->toContain("__('Payment Pending')")
        ->toContain('<ConfirmationModal')
        ->toContain('@confirm="executeConfirmedAction"');
});

test('admin subscription form uses searchable customer and plan selectors', function () {
    $subscriptionsIndexPage = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Subscriptions/Index.vue');

    expect($subscriptionsIndexPage)
        ->toContain('const filteredUsers = computed(() => {')
        ->toContain('const filteredPlans = computed(() => {')
        ->toContain('const openCustomerDropdown = () => {')
        ->toContain('const openPlanDropdown = () => {')
        ->toContain('ref="customerSearchInputRef"')
        ->toContain('ref="planSearchInputRef"')
        ->toContain('v-model="customerSearchQuery"')
        ->toContain('v-model="planSearchQuery"')
        ->toContain('@click="selectCustomer(user)"')
        ->toContain('@click="selectPlan(plan)"')
        ->toContain("{{ __('No customer found.') }}")
        ->toContain("{{ __('No plan found.') }}");
});
