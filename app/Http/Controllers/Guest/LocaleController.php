<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Services\LocalizationService;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function __construct(protected LocalizationService $localizationService) {}

    public function switch(string $locale): RedirectResponse
    {
        \Illuminate\Support\Facades\Log::info("LocaleController: Switching to {$locale}");
        $this->localizationService->switchLocale($locale);
        return redirect()->back();
    }
}
