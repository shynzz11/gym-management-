<?php

test('admin payments filter request, controller wiring, and page filters exist', function () {
    $request = file_get_contents(__DIR__.'/../../app/Http/Requests/Admin/FilterPaymentRequest.php');
    $controller = file_get_contents(__DIR__.'/../../app/Http/Controllers/Admin/PaymentController.php');
    $service = file_get_contents(__DIR__.'/../../app/Services/PaymentService.php');
    $page = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Payments/Index.vue');

    expect($request)
        ->toContain('class FilterPaymentRequest extends FormRequest')
        ->toContain("'customer_id'")
        ->toContain("'plan_id'")
        ->toContain("'type'")
        ->toContain("'method'")
        ->toContain("'status'");

    expect($controller)
        ->toContain('public function index(FilterPaymentRequest $request): Response')
        ->toContain('public function pending(FilterPaymentRequest $request): Response')
        ->toContain("authorize('view_pending_payments')")
        ->toContain('$filters = $request->validated();')
        ->toContain("'payments' => \$this->paymentService->getAll(\$filters)")
        ->toContain("'indexUrl' => \$indexUrl")
        ->toContain("'filterOptions' => \$this->paymentService->getFilterOptions()")
        ->toContain("'filters' => [");

    expect($service)
        ->toContain('public function getAll(array $filters = []): Collection')
        ->toContain("whereHas('subscription'")
        ->toContain("->where('method', \$method)")
        ->toContain("->where('status', \$status)")
        ->toContain("->where('transaction_type', \$type)")
        ->toContain('public function getFilterOptions(): array');

    expect($page)
        ->toContain('const customerId = ref(props.filters?.customer_id ? `${props.filters.customer_id}` : \'\');')
        ->toContain('const planId = ref(props.filters?.plan_id ? `${props.filters.plan_id}` : \'\');')
        ->toContain('const status = ref(props.filters?.status ?? \'\');')
        ->toContain('const customerSearchQuery = ref(\'\');')
        ->toContain('const planSearchQuery = ref(\'\');')
        ->toContain('const filteredCustomers = computed(() => {')
        ->toContain('const filteredPlans = computed(() => {')
        ->toContain('const paymentRows = computed(() => props.payments ?? []);')
        ->toContain('const isPendingOnlyView = computed(() => `${status.value}`.toLowerCase() === \'pending\');')
        ->toContain('const paymentSectionTitle = computed(() => {')
        ->toContain('const hasActiveFilters = computed(() => {')
        ->toContain('const openCustomerDropdown = () => {')
        ->toContain('const openPlanDropdown = () => {')
        ->toContain('const selectCustomer = (customer: any) => {')
        ->toContain('const selectPlan = (plan: any) => {')
        ->toContain('const clearCustomerFilter = (): void => {')
        ->toContain('const clearPlanFilter = (): void => {')
        ->toContain('ref="customerSearchInputRef"')
        ->toContain('ref="planSearchInputRef"')
        ->toContain('v-model="customerSearchQuery"')
        ->toContain('v-model="planSearchQuery"')
        ->toContain('@click="selectCustomer(customer)"')
        ->toContain('@click="selectPlan(plan)"')
        ->toContain('@click="clearCustomerFilter"')
        ->toContain('@click="clearPlanFilter"')
        ->toContain("{{ __('No customer found.') }}")
        ->toContain("{{ __('No plan found.') }}")
        ->toContain('const applyFilters = () => {')
        ->toContain('const resetFilters = () => {')
        ->toContain('props.indexUrl ?? admin.payments.index.url()')
        ->toContain('status: status.value || undefined')
        ->toContain("{{ __('Plan') }}")
        ->toContain('{{ __(paymentSectionTitle) }}')
        ->toContain('payment.subscription?.plan?.name')
        ->toContain('v-for="payment in filteredPaymentRows"')
        ->toContain(':disabled="!hasActiveFilters"')
        ->toContain('@click="resetFilters"');
});
