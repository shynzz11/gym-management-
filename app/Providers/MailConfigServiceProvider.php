<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MailConfigServiceProvider extends ServiceProvider
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
            $settings = \App\Models\Setting::where('key', 'like', 'MAIL_%')->pluck('value', 'key');

            if ($settings->isNotEmpty()) {
                config([
                    'mail.mailers.smtp.host' => $settings->get('MAIL_HOST', config('mail.mailers.smtp.host')),
                    'mail.mailers.smtp.port' => $settings->get('MAIL_PORT', config('mail.mailers.smtp.port')),
                    'mail.mailers.smtp.username' => $settings->get('MAIL_USERNAME', config('mail.mailers.smtp.username')),
                    'mail.mailers.smtp.password' => $settings->get('MAIL_PASSWORD', config('mail.mailers.smtp.password')),
                    'mail.mailers.smtp.encryption' => $settings->get('MAIL_ENCRYPTION', config('mail.mailers.smtp.encryption')),
                    'mail.from.address' => $settings->get('MAIL_FROM_ADDRESS', config('mail.from.address')),
                    'mail.from.name' => $settings->get('MAIL_FROM_NAME', config('mail.from.name')),
                ]);
            }
        } catch (\Exception $e) {
            // Avoid crashing if table doesn't exist yet
        }
    }
}
