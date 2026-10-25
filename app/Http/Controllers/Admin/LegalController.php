<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LegalController extends Controller
{
    public function __construct(protected \App\Services\BusinessSettingService $businessSettingService)
    {
    }

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

        $this->businessSettingService->updateSettings($request->validated());

        return back()->with('success', 'Legal documents updated successfully.');
    }
}
