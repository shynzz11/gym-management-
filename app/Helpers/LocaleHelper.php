<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocaleHelper
{
    /**
     * Get the current application locale.
     */
    public static function getLocale(): string
    {
        $id = Session::getId();
        $locale = Session::get('locale', config('localization.default', 'en'));
        \Illuminate\Support\Facades\Log::info("LocaleHelper: getLocale [SessionID: {$id}] returning {$locale}");
        return $locale;
    }

    /**
     * Set the application locale.
     */
    public static function setLocale(string $locale): void
    {
        $id = Session::getId();
         \Illuminate\Support\Facades\Log::info("LocaleHelper: setLocale [SessionID: {$id}] called with {$locale}");
        if (array_key_exists($locale, config('localization.supported'))) {
            Session::put('locale', $locale);
            App::setLocale($locale);
            \Illuminate\Support\Facades\Log::info("LocaleHelper: locale set to {$locale} in Session [SessionID: {$id}] and App");
        } else {
             \Illuminate\Support\Facades\Log::info("LocaleHelper: {$locale} not supported [SessionID: {$id}]");
        }
    }

    /**
     * Get the direction of the current locale (ltr/rtl).
     */
    public static function getDirection(): string
    {
        $locale = self::getLocale();
        return config("localization.supported.{$locale}.dir", 'ltr');
    }

    /**
     * Check if the current locale is RTL.
     */
    public static function isRtl(): bool
    {
        return self::getDirection() === 'rtl';
    }
}
