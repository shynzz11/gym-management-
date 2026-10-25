<?php

test('business settings no longer includes system language field', function () {
    $controller = file_get_contents(__DIR__.'/../../app/Http/Controllers/Admin/BusinessLogicController.php');
    $request = file_get_contents(__DIR__.'/../../app/Http/Requests/Admin/UpdateBusinessSettingsRequest.php');
    $page = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Business/BusinessLogic.vue');

    expect($controller)
        ->toContain("getSettings(['currency_symbol', 'timezone', 'country'])")
        ->not->toContain('language');

    expect($request)
        ->toContain("'country' => 'required|string|max:100'")
        ->not->toContain("'language' =>")
        ->not->toContain('language.required')
        ->not->toContain('language.in');

    expect($page)
        ->not->toContain('System Language')
        ->not->toContain('form.language')
        ->not->toContain('const languages =');
});
