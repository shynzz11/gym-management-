<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use App\Traits\UploadsMedia;

class ProfileController extends Controller
{
    public function __construct(protected \App\Services\UserService $userService) {}

    public function edit()
    {
        return Inertia::render('Admin/Pages/Profile');
    }

    public function update(\App\Http\Requests\Admin\UpdateAdminProfileRequest $request)
    {
        $this->userService->update(
            $request->user(), 
            $request->validated(), 
            $request->file('profile_image')
        );

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }
}
