<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Permissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendTestEmailRequest;
use App\Http\Requests\Admin\UpdateEmailSettingsRequest;
use App\Mail\TestSmtpMail;
use App\Models\Setting;
use App\Services\SmtpSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Inertia\Response;

class EmailSettingsController extends Controller
{
    public function index(): Response
    {
        Gate::authorize(Permissions::EMAIL_SETTINGS);

        $settings = Setting::query()->where('key', 'like', 'MAIL_%')->pluck('value', 'key');

        if (config('app.mode') === 'demo' && $settings->has('MAIL_PASSWORD')) {
            $settings['MAIL_PASSWORD'] = '******';
        }

        return inertia('Admin/Business/EmailSettings', [
            'settings' => $settings,
        ]);
    }

    public function update(UpdateEmailSettingsRequest $request, SmtpSettingsService $smtpSettings): RedirectResponse
    {
        Gate::authorize(Permissions::EMAIL_SETTINGS);

        if (config('app.mode') === 'demo') {
            return back()->with('error', 'Email settings update is disabled in demo mode.');
        }

        $validated = $request->validated();

        foreach ($validated as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value]
            );
        }

        $smtpSettings->apply($validated);
        Cache::forget('business_settings_all');

        return back()->with('success', 'Email settings updated successfully.');
    }

    public function sendTest(SendTestEmailRequest $request): RedirectResponse
    {
        Gate::authorize(Permissions::EMAIL_SETTINGS);

        if (config('app.mode') === 'demo') {
            return back()->with('error', 'Sending test emails is disabled in demo mode.');
        }

        $validated = $request->validated();

        $this->applyMailConfiguration($validated);

        try {
            Mail::mailer('smtp')
                ->to($validated['test_email'])
                ->send(new TestSmtpMail($validated['MAIL_FROM_NAME']));
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['test_email' => 'Unable to send test email. Please verify SMTP settings and try again.'])
                ->withInput();
        }

        return back()->with('success', 'Test email sent successfully.');
    }

    protected function applyMailConfiguration(array $settings): void
    {
        $mailEncryption = $settings['MAIL_ENCRYPTION'] ?? null;

        if ($mailEncryption === 'none' || $mailEncryption === '') {
            $mailEncryption = null;
        }

        config([
            'mail.mailer' => 'smtp',
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $settings['MAIL_HOST'],
            'mail.mailers.smtp.port' => (int) $settings['MAIL_PORT'],
            'mail.mailers.smtp.username' => $settings['MAIL_USERNAME'],
            'mail.mailers.smtp.password' => $settings['MAIL_PASSWORD'],
            'mail.mailers.smtp.encryption' => $mailEncryption,
            'mail.from.address' => $settings['MAIL_FROM_ADDRESS'],
            'mail.from.name' => $settings['MAIL_FROM_NAME'],
        ]);

        app('mail.manager')->purge('smtp');
    }
}
