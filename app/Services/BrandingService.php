<?php

namespace App\Services;

use App\Models\Brand;
use App\Traits\LogsActivity;

class BrandingService
{
    use LogsActivity, \App\Traits\UploadsMedia;

    public function updateSettings(Brand $brand, array $settings, $logo = null): Brand
    {
        if ($logo) {
            $brand->logo = $this->uploadOne($logo, 'branding');
        }

        $brand->settings = array_merge($brand->settings ?? [], $settings);
        $brand->save();

        $this->logActivity('update_branding', "Updated branding for {$brand->name}");

        return $brand;
    }
}
