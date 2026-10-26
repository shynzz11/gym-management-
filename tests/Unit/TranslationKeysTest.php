<?php

test('disabled translation key exists in english and arabic locales', function () {
    $enTranslations = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/lang/en.json'), true);
    $arTranslations = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/lang/ar.json'), true);

    expect($enTranslations)->toBeArray()
        ->and($enTranslations)->toHaveKey('Disabled')
        ->and($enTranslations['Disabled'])->toBe('Disabled')
        ->and($arTranslations)->toBeArray()
        ->and($arTranslations)->toHaveKey('Disabled')
        ->and($arTranslations['Disabled'])->toBe('معطل');
});
