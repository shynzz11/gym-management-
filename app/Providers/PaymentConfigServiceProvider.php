<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class PaymentConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole() && $this->app->runningUnitTests()) {
            return;
        }

        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $settings = Setting::query()
                ->whereIn('key', [
                    'SSLCOMMERZ_ENABLED',
                    'SSLCOMMERZ_STORE_ID',
                    'SSLCOMMERZ_STORE_PASSWORD',
                    'SSLCOMMERZ_SANDBOX',
                    'STRIPE_ENABLED',
                    'STRIPE_PUBLIC_KEY',
                    'STRIPE_SECRET_KEY',
                    'STRIPE_WEBHOOK_SECRET',
                ])
                ->pluck('value', 'key');

            config([
                'sslcommerz.store_id' => (string) $settings->get('SSLCOMMERZ_STORE_ID', config('sslcommerz.store_id')),
                'sslcommerz.store_password' => (string) $settings->get('SSLCOMMERZ_STORE_PASSWORD', config('sslcommerz.store_password')),
                'sslcommerz.sandbox' => filter_var(
                    $settings->get('SSLCOMMERZ_SANDBOX', config('sslcommerz.sandbox')),
                    FILTER_VALIDATE_BOOL
                ),
                'payment.gateways.sslcommerz.enabled' => filter_var(
                    $settings->get('SSLCOMMERZ_ENABLED', config('payment.gateways.sslcommerz.enabled', true)),
                    FILTER_VALIDATE_BOOL
                ),
                'payment.gateways.stripe.enabled' => filter_var(
                    $settings->get('STRIPE_ENABLED', config('payment.gateways.stripe.enabled', true)),
                    FILTER_VALIDATE_BOOL
                ),
                'cashier.key' => (string) $settings->get('STRIPE_PUBLIC_KEY', config('cashier.key')),
                'cashier.secret' => (string) $settings->get('STRIPE_SECRET_KEY', config('cashier.secret')),
                'cashier.webhook.secret' => (string) $settings->get('STRIPE_WEBHOOK_SECRET', config('cashier.webhook.secret')),
                'services.stripe.key' => (string) $settings->get('STRIPE_PUBLIC_KEY', config('services.stripe.key')),
                'services.stripe.secret' => (string) $settings->get('STRIPE_SECRET_KEY', config('services.stripe.secret')),
                'services.stripe.webhook.secret' => (string) $settings->get('STRIPE_WEBHOOK_SECRET', config('services.stripe.webhook.secret')),
            ]);
        } catch (\Throwable) {
            // Silent fail during early bootstrap or migration steps.
        }
    }
}
