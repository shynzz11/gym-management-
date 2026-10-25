<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Traits\LogsActivity;
use App\Traits\UploadsMedia;
use Illuminate\Support\Facades\Cache;

class BusinessSettingService
{
    use LogsActivity;
    use UploadsMedia;

    public function getSettings(array $keys = [], bool $formatForFrontend = false)
    {
        $query = BusinessSetting::query();

        if (! empty($keys)) {
            $query->whereIn('key', $keys);
        }

        $settings = $query->pluck('value', 'key');

        if ($formatForFrontend) {
            return $settings->map(function ($value, $key) {
                if (in_array($key, ['logo_url', 'favicon_url', 'cover_url']) && $value && ! str_starts_with($value, 'http')) {
                    return '/storage/'.$value;
                }

                return $value;
            });
        }

        return $settings;
    }

    public function updateBranding(array $data, array $files = []): void
    {
        foreach ($files as $key => $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $data[$key] = $this->uploadOne($file, 'branding');
            }
        }

        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $value = '';
            }

            // Fix for /storage/ prefix duplication
            if (in_array($key, ['logo_url', 'favicon_url', 'cover_url']) && is_string($value) && str_starts_with($value, '/storage/')) {
                $value = substr($value, 9);
            }

            BusinessSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        Cache::forget('business_settings_all');
        $this->logActivity('update_branding', 'Updated branding settings');
    }

    public function updateSettings(array $data): void
    {
        foreach ($data as $key => $value) {
            BusinessSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
        }

        Cache::forget('business_settings_all');
        $this->logActivity('update_settings', 'Updated business settings: '.implode(', ', array_keys($data)));
    }
}
