<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;

class BusinessSetting extends BaseModel
{
    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget('business_settings_all');
        });

        static::deleted(function (): void {
            Cache::forget('business_settings_all');
        });
    }
}
