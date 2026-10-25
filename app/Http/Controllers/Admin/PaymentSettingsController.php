<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Permissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePaymentSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Inertia\Response;

class PaymentSettingsController extends Controller
{
    public function index(): Response
    {
        Gate::authorize(Permissions::PAYMENT_GATEWAY_SETUP);

        $settings = Setting::query()->whereIn('key', [
            'SSLCOMMERZ_ENABLED',
            'SSLCOMMERZ_STORE_ID',
            'SSLCOMMERZ_STORE_PASSWORD',
            'SSLCOMMERZ_SANDBOX',
            'STRIPE_ENABLED',
            'STRIPE_PUBLIC_KEY',
            'STRIPE_SECRET_KEY',
            'STRIPE_WEBHOOK_SECRET',
        ])->pluck('value', 'key');

        $isDemo = config('app.mode') === 'demo';
        $mask = '******';

        return inertia('Admin/Business/PaymentSettings', [
            'settings' => [
                'SSLCOMMERZ_ENABLED' => filter_var($settings->get('SSLCOMMERZ_ENABLED', false), FILTER_VALIDATE_BOOL),
                'SSLCOMMERZ_STORE_ID' => $isDemo ? $mask : $settings->get('SSLCOMMERZ_STORE_ID', ''),
                'SSLCOMMERZ_STORE_PASSWORD' => $isDemo ? $mask : $settings->get('SSLCOMMERZ_STORE_PASSWORD', ''),
                'SSLCOMMERZ_SANDBOX' => filter_var($settings->get('SSLCOMMERZ_SANDBOX', true), FILTER_VALIDATE_BOOL),
                'STRIPE_ENABLED' => filter_var($settings->get('STRIPE_ENABLED', false), FILTER_VALIDATE_BOOL),
                'STRIPE_PUBLIC_KEY' => $isDemo ? $mask : $settings->get('STRIPE_PUBLIC_KEY', ''),
                'STRIPE_SECRET_KEY' => $isDemo ? $mask : $settings->get('STRIPE_SECRET_KEY', ''),
                'STRIPE_WEBHOOK_SECRET' => $isDemo ? $mask : $settings->get('STRIPE_WEBHOOK_SECRET', ''),
            ],
        ]);
    }

    public function update(UpdatePaymentSettingsRequest $request): RedirectResponse
    {
        Gate::authorize(Permissions::PAYMENT_GATEWAY_SETUP);

        if (config('app.mode') === 'demo') {
            return back()->with('error', 'Payment settings update is disabled in demo mode.');
        }

        $validated = $request->validated();

        foreach ($validated as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? '')]
            );
        }

        Cache::forget('business_settings_all');

        return back()->with('success', 'Payment settings updated successfully.');
    }
}
