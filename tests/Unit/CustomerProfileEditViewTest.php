<?php

test('admin member edit page includes customer profile fields for customer users', function () {
    $editPage = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Members/Edit.vue');
    $updateRequest = file_get_contents(__DIR__.'/../../app/Http/Requests/Admin/UpdateUserRequest.php');
    $userService = file_get_contents(__DIR__.'/../../app/Services/UserService.php');
    $userController = file_get_contents(__DIR__.'/../../app/Http/Controllers/Admin/UserController.php');

    expect($editPage)
        ->toContain("const isCustomerUser = computed(() => props.directoryType === 'customer' || props.user.type === 'customer');")
        ->toContain('const currentPlans = computed(() => props.activeSubscriptions ?? []);')
        ->toContain('const directoryIndexUrl = computed(() => (isCustomerUser.value ? admin.customers.index.url() : admin.staff.index.url()));')
        ->toContain('const updateUrl = computed(() => (isCustomerUser.value ? admin.customers.update.url(props.user.id) : admin.staff.update.url(props.user.id)));')
        ->toContain('COUNTRY_PHONE_CODES')
        ->toContain("import dayjs from 'dayjs';")
        ->toContain("{{ __('Customer Plans') }}")
        ->toContain("{{ __('No active or pending plans assigned.') }}")
        ->toContain('splitPhoneByDialCode(form.phone, defaultCountryIso)')
        ->toContain('const phoneCountryIso = ref(initialPhoneParts.countryIso);')
        ->toContain('v-model="countrySearch"')
        ->toContain('v-model="phoneLocalNumber"')
        ->toContain('composePhoneFromDialCode(selectedCountryOption.value.dialCode, sanitizedPhoneDigits.value)')
        ->toContain('v-model="form.address"')
        ->toContain('props.user.customer_profile?.phone')
        ->toContain('props.user.customer_profile?.address')
        ->toContain(':href="directoryIndexUrl"')
        ->toContain('.put(updateUrl.value, {');

    expect($updateRequest)
        ->toContain("\$isCustomerUpdateRoute = \$this->routeIs('admin.customers.update');")
        ->toContain("'phone' => \$isCustomerUpdateRoute")
        ->toContain("Rule::unique('customer_profiles', 'phone')->ignore(\$userId, 'user_id')")
        ->toContain("'address' => 'nullable|string|max:1000'")
        ->toContain("'role_id' => 'nullable|exists:roles,id'");

    expect($userService)
        ->toContain("['roles', 'phone', 'address']")
        ->toContain('customerProfile()->updateOrCreate');

    expect($userController)
        ->toContain("\$user->load(['roles', 'customerProfile', 'subscriptions.plan']);")
        ->toContain('public function editCustomer(User $user): \Inertia\Response')
        ->toContain('public function updateCustomer(\App\Http\Requests\Admin\UpdateUserRequest $request, User $user): \Illuminate\Http\RedirectResponse')
        ->toContain("'directoryType' => 'customer'")
        ->toContain("'activeSubscriptions' => \$activeSubscriptions")
        ->toContain("to_route(\$user->isCustomer() ? 'admin.customers.index' : 'admin.staff.index')");
});
