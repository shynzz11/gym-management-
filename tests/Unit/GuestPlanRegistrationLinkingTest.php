<?php

test('landing page select plan buttons pass selected plan id to register route', function () {
    $landingPage = file_get_contents(__DIR__.'/../../resources/js/Pages/Guest/LandingPage.vue');

    expect($landingPage)
        ->toContain(':href="register.url({ query: { plan_id: plan.id } })"')
        ->toContain("{{ __('Select Plan') }}")
        ->toContain("{{ __('Claim Offer') }}");
});

test('register page preselects plan from query string when valid plan id is provided', function () {
    $registerPage = file_get_contents(__DIR__.'/../../resources/js/Pages/Guest/Pages/Register.vue');

    expect($registerPage)
        ->toContain('const applyPreselectedPlanFromUrl = (): void => {')
        ->toContain('const params = new URLSearchParams(queryString);')
        ->toContain("const preselectedPlanId = params.get('plan_id');")
        ->toContain('const matchedPlan = props.plans.find((plan) => String(plan.id) === preselectedPlanId);')
        ->toContain('form.plan_id = matchedPlan.id;')
        ->toContain('applyPreselectedPlanFromUrl();');
});
