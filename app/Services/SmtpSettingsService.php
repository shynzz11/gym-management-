<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SmtpSettingsService
{
    private const KEYS = [
        'MAIL_HOST',
        'MAIL_PORT',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_ENCRYPTION',
        'MAIL_FROM_ADDRESS',
        'MAIL_FROM_NAME',
    ];

    public function applySavedSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $this->apply(Setting::query()->whereIn('key', self::KEYS)->pluck('value', 'key')->all());
        } catch (QueryException $exception) {
            Log::warning('Saved SMTP settings could not be loaded because the database is unavailable.', [
                'exception' => $exception,
            ]);
        }
    }

    public function apply(array $settings): void
    {
        foreach (['MAIL_HOST', 'MAIL_PORT', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME'] as $requiredKey) {
            if (! isset($settings[$requiredKey]) || trim((string) $settings[$requiredKey]) === '') {
                return;
            }
        }

        $encryption = $settings['MAIL_ENCRYPTION'] ?? null;
        if ($encryption === 'none' || $encryption === '') {
            $encryption = null;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $settings['MAIL_HOST'],
            'mail.mailers.smtp.port' => (int) $settings['MAIL_PORT'],
            'mail.mailers.smtp.username' => $settings['MAIL_USERNAME'] ?? null,
            'mail.mailers.smtp.password' => $settings['MAIL_PASSWORD'] ?? null,
            'mail.mailers.smtp.encryption' => $encryption,
            'mail.from.address' => $settings['MAIL_FROM_ADDRESS'],
            'mail.from.name' => $settings['MAIL_FROM_NAME'],
        ]);

        app('mail.manager')->purge('smtp');
    }
}
