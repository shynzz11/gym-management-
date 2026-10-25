<?php

test('customer renew modal uses current plan display without selector', function () {
    $dashboardPage = file_get_contents(__DIR__.'/../../resources/js/Pages/Customer/Pages/Dashboard.vue');

    expect($dashboardPage)
        ->toContain("const isRenewAction = computed(() => planActionType.value === 'renew');")
        ->toContain('const showJoinPlanDropdown = ref(false);')
        ->toContain('const joinPlanSearchQuery = ref(\'\');')
        ->toContain('const filteredJoinablePlans = computed(() => {')
        ->toContain('const selectedJoinPlanLabel = computed(() => {')
        ->toContain('const openJoinPlanDropdown = (): void => {')
        ->toContain('const selectJoinPlan = (plan: any): void => {')
        ->toContain('const renewPlanDisplayLabel = computed(() => {')
        ->toContain('const canSubmitPlanAction = computed(() => {')
        ->toContain("planRequestForm.plan_id = action === 'renew' && selectedSubscription.value")
        ->toContain(': \'\';')
        ->toContain('const planId = isRenewAction.value ?')
        ->toContain(': planRequestForm.plan_id;')
        ->toContain("v-if=\"planActionType === 'renew'\"")
        ->toContain('{{ renewPlanDisplayLabel }}')
        ->toContain('v-else')
        ->toContain('selectedJoinPlanLabel')
        ->toContain('v-model="joinPlanSearchQuery"')
        ->toContain('v-for="plan in filteredJoinablePlans"')
        ->toContain('@click="selectJoinPlan(plan)"')
        ->toContain(':disabled="!canSubmitPlanAction"');
});
