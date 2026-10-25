<?php

use App\Models\BusinessSetting;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('Indian rupee settings resolve to INR and select the configured compatible gateway', function () {
    BusinessSetting::query()->updateOrCreate(
        ['key' => 'currency_symbol'],
        ['value' => '₹', 'type' => 'string'],
    );

    config([
        'payment.gateways.stripe.enabled' => true,
        'payment.gateways.sslcommerz.enabled' => true,
    ]);

    $service = new PaymentGatewayService;

    expect($service->resolveSystemCurrencyCode())->toBe('INR')
        ->and($service->isGatewayAvailableForSystemCurrency('sslcommerz'))->toBeFalse()
        ->and($service->isGatewayAvailableForSystemCurrency('stripe'))->toBeTrue()
        ->and($service->resolveGatewayBySystemCurrency())->toBe('stripe');
});

test('currency migration replaces taka defaults without changing other configured currencies', function () {
    BusinessSetting::query()->updateOrCreate(
        ['key' => 'currency_symbol'],
        ['value' => '৳', 'type' => 'string'],
    );
    BusinessSetting::query()->updateOrCreate(
        ['key' => 'custom_currency'],
        ['value' => '$', 'type' => 'string'],
    );

    $migration = require database_path('migrations/2026_10_25_000001_replace_default_taka_with_rupee.php');
    $migration->up();

    expect(BusinessSetting::query()->where('key', 'currency_symbol')->value('value'))->toBe('₹')
        ->and(BusinessSetting::query()->where('key', 'custom_currency')->value('value'))->toBe('$');
});
