<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Admin/Auth/Login');
    }

    public function storeLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password') + [
            'type' => [UserType::ADMIN->value, UserType::SUPER_ADMIN->value],
        ];

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            /** @var \App\Models\User $user */
            $user = Auth::guard('admin')->user();

            if (! $user || (! $user->isAdmin() && ! $user->isSuperAdmin())) {
                Auth::guard('admin')->logout();

                return redirect()->back()->with('error', __('Only admin accounts can sign in here.'));
            }

            if ($intended = redirect()->getIntendedUrl()) {
                if (! str_starts_with($intended, url('/admin'))) {
                    $request->session()->forget('url.intended');

                    return redirect()->route('admin.dashboard');
                }
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function destroy(Request $request)
    {
        Auth::guard('admin')->logout();

        return redirect()->route('admin.login');
    }
}
