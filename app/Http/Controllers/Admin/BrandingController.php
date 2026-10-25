<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBrandingRequest;
use App\Models\Brand;
use App\Services\BrandingService;
use Inertia\Inertia;

class BrandingController extends Controller
{
    public function __construct(protected BrandingService $brandingService) {}

    public function index()
    {
        return Inertia::render('Admin/Pages/Branding', [
            'brand' => Brand::first(),
        ]);
    }

    public function update(UpdateBrandingRequest $request, Brand $brand)
    {
        $this->brandingService->updateSettings(
            $brand, 
            $request->validated()['settings'],
            $request->file('logo')
        );
        return redirect()->back()->with('success', 'Branding updated successfully.');
    }
}
