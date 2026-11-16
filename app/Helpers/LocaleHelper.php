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
        return Session::get('locale', config('localization.default', 'en'));
    }

    /**
     * Set the application locale.
     */
    public static function setLocale(string $locale): void
    {
        if (array_key_exists($locale, config('localization.supported'))) {
            Session::put('locale', $locale);
            App::setLocale($locale);
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
