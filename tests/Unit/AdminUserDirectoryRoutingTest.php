<?php

test('admin users and customers directories are wired with separate filters and navigation', function () {
    $userController = file_get_contents(__DIR__.'/../../app/Http/Controllers/Admin/UserController.php');
    $userService = file_get_contents(__DIR__.'/../../app/Services/UserService.php');
    $adminRoutes = file_get_contents(__DIR__.'/../../routes/admin.php');
    $permissions = file_get_contents(__DIR__.'/../../app/Constants/Permissions.php');
    $storeUserRequest = file_get_contents(__DIR__.'/../../app/Http/Requests/Admin/StoreUserRequest.php');
    $membersIndexPage = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Members/Index.vue');
    $membersCreatePage = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Members/Create.vue');
    $adminSidebar = file_get_contents(__DIR__.'/../../resources/js/Components/Admin/Sidebar.vue');

    expect($userController)
        ->toContain("'directory' => 'staff'")
        ->toContain('public function customers(): \Inertia\Response')
        ->toContain('public function createCustomer(): \Inertia\Response')
        ->toContain('public function editCustomer(User $user): \Inertia\Response')
        ->toContain("'directory' => 'customer'")
        ->toContain("'directoryType' => 'staff'")
        ->toContain("'directoryType' => 'customer'")
        ->toContain("'plans' => []")
        ->toContain("->where('is_active', true)")
        ->toContain("'customerIndexUrl' => route('admin.customers.index')")
        ->toContain("'plans' => Plan::query()")
        ->toContain("\$selectedPlanId = \$data['plan_id'] ?? null;")
        ->toContain('$this->subscriptionService->create([')
        ->toContain('$data[\'type\'] = UserType::ADMIN->value;')
        ->toContain('$data[\'type\'] = UserType::CUSTOMER->value;');

    expect($adminRoutes)
        ->toContain("Route::get('customers', [UserController::class, 'customers'])->name('customers.index')->middleware('supreme');")
        ->toContain("Route::get('customers/create', [UserController::class, 'createCustomer'])->name('customers.create')->middleware('supreme');")
        ->toContain("Route::post('customers', [UserController::class, 'storeCustomer'])->name('customers.store')->middleware('supreme');")
        ->toContain("Route::get('customers/{user}/edit', [UserController::class, 'editCustomer'])->name('customers.edit')->middleware('supreme');")
        ->toContain("Route::put('customers/{user}', [UserController::class, 'updateCustomer'])->name('customers.update')->middleware('supreme');");

    expect($userService)
        ->toContain('UserType::ADMIN->value')
        ->toContain('UserType::SUPER_ADMIN->value')
        ->toContain('UserType::CUSTOMER->value')
        ->toContain("with(['roles', 'subscriptions', 'customerProfile'])")
        ->toContain('where(function ($searchQuery) use ($search) {');

    expect($permissions)
        ->toContain("'label' => 'customer_list'")
        ->toContain("'route' => 'admin.customers.index'");

    expect($storeUserRequest)
        ->toContain("\$isCustomerCreateRoute = \$this->routeIs('admin.customers.store');")
        ->toContain("'password' => 'required|string|min:8|confirmed'")
        ->toContain("'phone' => \$isCustomerCreateRoute")
        ->toContain("Rule::unique('customer_profiles', 'phone')")
        ->toContain("'plan_id' => [")
        ->toContain("Rule::exists('plans', 'id')->where('is_active', true)");

    expect($membersIndexPage)
        ->toContain('directoryType')
        ->toContain('const hasUsers = computed(() => (props.users?.data ?? []).length > 0);')
        ->toContain('customerIndexUrl')
        ->toContain('const customerCreateUrl = computed(() => admin.customers.create.url());')
        ->toContain('const showCustomerDetailsModal = ref(false);')
        ->toContain('const openCustomerDetails = (user: any): void => {')
        ->toContain("{{ __('Customer Details') }}")
        ->toContain('@click="openCustomerDetails(user)"')
        ->toContain('const tableColumnCount = computed(() => (isCustomerDirectory.value ? 5 : 4));')
        ->toContain("isCustomerDirectory ? __('Customer List') : __('Staff List')")
        ->toContain("isCustomerDirectory ? __('Search customers...') : __('Search staff...')")
        ->toContain("{{ __('Create Staff') }}")
        ->toContain("{{ __('Create Customer') }}")
        ->toContain("{{ isCustomerDirectory ? __('Phone') : __('Access Role') }}")
        ->toContain("{{ __('Address') }}")
        ->toContain("{{ __('Assigned Plans') }}")
        ->toContain('const getCustomerSubscriptions = (user: any): any[] => {')
        ->toContain('const getVisibleCustomerSubscriptions = (user: any): any[] => {')
        ->toContain('const getRemainingSubscriptionCount = (user: any): number => {')
        ->toContain('const formatSubscriptionStatus = (status: string | null | undefined): string => {')
        ->toContain("{{ __('No plan assigned') }}")
        ->toContain('v-if="!isCustomerDirectory"')
        ->toContain('user.customer_profile?.phone')
        ->toContain('user.customer_profile?.address')
        ->toContain("{{ __('Assign Plan') }}")
        ->toContain('const showAssignPlanModal = ref(false);')
        ->toContain('const openAssignPlanModal = (user: any): void => {')
        ->toContain('.post(admin.subscriptions.store.url(), {')
        ->toContain('@click="openAssignPlanModal(user)"')
        ->toContain(':href="isCustomerDirectory ? admin.customers.edit.url(user.id) : admin.staff.edit.url(user.id)"')
        ->toContain("? __('No customers found yet.')")
        ->toContain(": __('No staff found yet.')");

    expect($membersIndexPage)->not->toContain("{{ __('Plan Name') }}");

    expect($membersCreatePage)
        ->toContain('plans: Array as PropType<any[]>')
        ->toContain('directoryType')
        ->toContain('const selectedPlan = computed(() => {')
        ->toContain('COUNTRY_PHONE_CODES')
        ->toContain("const defaultCountryIso = 'US';")
        ->toContain('const phoneCountryIso = ref(initialPhoneParts.countryIso);')
        ->toContain('v-model="countrySearch"')
        ->toContain('v-model="phoneLocalNumber"')
        ->toContain('composePhoneFromDialCode(selectedCountryOption.value.dialCode, sanitizedPhoneDigits.value)')
        ->toContain("{{ __('Select Plan (Optional)') }}")
        ->toContain('v-model="form.plan_id"')
        ->toContain('const storeUrl = computed(() => (isCustomerDirectory.value ? admin.customers.store.url() : admin.staff.store.url()));')
        ->toContain("isCustomerDirectory ? __('Create Customer') : __('Create Staff')");

    expect($adminSidebar)->toContain("url.startsWith('/admin/customers')");
});
