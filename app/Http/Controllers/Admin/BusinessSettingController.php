<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BusinessSettingController extends Controller
{
    public function __construct(protected \App\Services\BusinessSettingService $businessSettingService) {}

    public function index()
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::BUSINESS_BRANDING);

        return inertia('Admin/Business/Branding', [
            'settings' => $this->businessSettingService->getSettings([], true),
            'default_colors' => config('branding.colors'),
        ]);
    }

    public function update(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::BUSINESS_BRANDING);

        $data = $request->except(['_token', '_method']);
        $files = [
            'logo_url' => $request->file('logo_url'),
            'favicon_url' => $request->file('favicon_url'),
            'cover_url' => $request->file('cover_url'),
        ];

        $this->businessSettingService->updateBranding($data, $files);

        return back()->with('success', 'Settings updated successfully.');
    }
}
