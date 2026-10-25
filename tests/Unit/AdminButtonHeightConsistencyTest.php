<?php

test('admin workspace enforces consistent action button height', function () {
    $adminLayout = file_get_contents(__DIR__.'/../../resources/js/Layouts/AdminLayout.vue');
    $appCss = file_get_contents(__DIR__.'/../../resources/css/app.css');

    expect($adminLayout)->toContain('class="admin-workspace flex-1 space-y-6 overflow-y-auto p-4 lg:p-6"');

    expect($appCss)
        ->toContain('@layer components {')
        ->toContain(".admin-workspace :is(a, button, input[type='button'], input[type='submit'], input[type='reset'])[class*='rounded-2xl']:not([class*='h-'])")
        ->toContain('min-height: 2.75rem;');
});
