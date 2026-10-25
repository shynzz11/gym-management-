<?php

namespace App\Services;

use App\Helpers\LocaleHelper;

class LocalizationService
{
    public function switchLocale(string $locale): void
    {
        LocaleHelper::setLocale($locale);
    }
}
