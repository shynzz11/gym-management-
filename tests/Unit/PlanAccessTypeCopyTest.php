<?php

test('access types index and form modal use plan access type copy', function () {
    $indexPage = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Plans/AccessTypes/Index.vue');
    $formModal = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Plans/AccessTypes/Partials/AccessTypeFormModal.vue');

    expect($indexPage)
        ->toContain("__('Plan Access Types')")
        ->toContain("__('Create Plan Access Type')")
        ->toContain("__('Delete Plan Access Type?')")
        ->toContain("import AppTooltip from '@/Components/Common/AppTooltip.vue';")
        ->toContain('canDeleteAccessType')
        ->toContain('getDeleteDisabledReason')
        ->toContain('<AppTooltip :text="!canToggleActiveStatus(type) ? __(\'At least one active access type is required.\') : \'\'">')
        ->toContain('<AppTooltip :text="getDeleteDisabledReason(type) ? __(getDeleteDisabledReason(type) as string) : \'\'">')
        ->toContain('requestStatusToggle')
        ->toContain("__('Deactivate Access Type?')")
        ->toContain("__('Activate Access Type?')")
        ->toContain('toggleActive.url(accessTypeToToggle.value.id)')
        ->toContain('destroy.url(accessTypeToDelete.value.id)')
        ->toContain('<Power :size="18" class="pointer-events-none" />')
        ->toContain('<Trash2 :size="18" class="pointer-events-none" />');

    expect($formModal)
        ->toContain("__('Edit Plan Access Type')")
        ->toContain("__('Create Plan Access Type')")
        ->toContain("__('Create Access Type')");
});

test('plan form defaults access type using available options', function () {
    $planFormModal = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Plans/Partials/PlanFormModal.vue');
    $plansIndexPage = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Plans/Index.vue');
    $planController = file_get_contents(__DIR__.'/../../app/Http/Controllers/Admin/PlanController.php');

    expect($planFormModal)
        ->toContain("import admin from '@/routes/admin';")
        ->toContain('const resolveDefaultAccessType = (plan: any = props.plan): string => {')
        ->toContain('return getSelectableAccessTypes(plan)?.[0]?.slug ?? \'\';')
        ->toContain('access_type: resolveDefaultAccessType(),')
        ->toContain('form.access_type = resolveDefaultAccessType();')
        ->toContain('offer_price: data.is_offer ? data.offer_price : null,')
        ->toContain('offer_ends_at: data.is_offer ? toUTCISO(data.offer_ends_at) : null,')
        ->toContain('.put(admin.plans.update.url(props.plan.id), {')
        ->toContain('.post(admin.plans.store.url(), {');

    expect($plansIndexPage)
        ->toContain("import admin from '@/routes/admin';")
        ->toContain('admin.plans.toggleActive.url(planToToggle.value.id)')
        ->toContain('admin.plans.destroy.url(planToDelete.value.id)');

    expect($planController)
        ->toContain("->orderByDesc('is_active')")
        ->toContain("->orderBy('name')");
});
