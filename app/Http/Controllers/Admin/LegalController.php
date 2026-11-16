<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;

class LegalController extends Controller
{
    public function __construct(protected \App\Services\BusinessSettingService $businessSettingService) {}

    public function index()
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::LEGAL_MANAGEMENT);

        $keys = ['privacy_policy', 'terms_and_conditions', 'about_us'];

        return inertia('Admin/Legal/Index', [
            'settings' => BusinessSetting::whereIn('key', $keys)->pluck('value', 'key'),
        ]);
    }

    public function update(\App\Http\Requests\Admin\UpdateLegalRequest $request)
    {
        \Illuminate\Support\Facades\Gate::authorize(\App\Constants\Permissions::LEGAL_MANAGEMENT);

        // Legal pages are rendered with v-html on public routes, so only store allowlisted markup.
        $this->businessSettingService->updateSettings(
            array_map(\App\Support\HtmlSanitizer::clean(...), $request->validated()),
        );

        return back()->with('success', 'Legal documents updated successfully.');
    }
}
