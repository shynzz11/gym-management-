<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\UpdateProfileRequest;
use App\Services\UserService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function __construct(protected UserService $userService) {}

    public function edit(Request $request)
    {
        $user = $request->user()?->loadMissing('customerProfile');

        return Inertia::render('Customer/Pages/Profile', [
            'user' => $user,
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $this->userService->update($request->user(), $request->validated());

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }
}
