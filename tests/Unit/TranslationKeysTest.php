<?php

test('email settings translation key exists in english locale', function () {
    $translations = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/lang/en.json'), true);

    expect($translations)->toBeArray()
        ->and($translations)->toHaveKey('email_settings')
        ->and($translations['email_settings'])->toBe('Email Settings');
});

test('email settings translation key exists in arabic locale', function () {
    $translations = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/lang/ar.json'), true);

    expect($translations)->toBeArray()
        ->and($translations)->toHaveKey('email_settings')
        ->and($translations['email_settings'])->toBe('إعدادات البريد الإلكتروني');
});

test('payment gateway translation key exists in english locale', function () {
    $translations = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/lang/en.json'), true);

    expect($translations)->toBeArray()
        ->and($translations)->toHaveKey('payment_gateway_setup')
        ->and($translations['payment_gateway_setup'])->toBe('Payment Gateway');
});

test('payment gateway translation key exists in arabic locale', function () {
    $translations = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/lang/ar.json'), true);

    expect($translations)->toBeArray()
        ->and($translations)->toHaveKey('payment_gateway_setup')
        ->and($translations['payment_gateway_setup'])->toBe('بوابة الدفع');
});

test('optional stripe webhook copy exists in english and arabic locales', function () {
    $enTranslations = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/lang/en.json'), true);
    $arTranslations = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/lang/ar.json'), true);

    expect($enTranslations)->toBeArray()
        ->and($enTranslations)->toHaveKey('Webhook Secret (Optional)')
        ->and($enTranslations)->toHaveKey('Stripe checkout still works without webhook using success callback verification.')
        ->and($arTranslations)->toBeArray()
        ->and($arTranslations)->toHaveKey('Webhook Secret (Optional)')
        ->and($arTranslations)->toHaveKey('Stripe checkout still works without webhook using success callback verification.');
});

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
