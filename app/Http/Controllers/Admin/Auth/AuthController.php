<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
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

        if (Auth::guard('admin')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();

            /** @var \App\Models\User $user */
            $user = Auth::guard('admin')->user();

            if (! $user || (! $user->isAdmin() && ! $user->isSuperAdmin())) {
                Auth::guard('admin')->logout();

                return redirect()->back()->with('error', __('Customers must login via the Customer Portal.'));
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

    public function showRegister()
    {
        return Inertia::render('Admin/Auth/Register');
    }

    public function storeRegister(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => UserType::ADMIN,
        ]);

        $user->adminProfile()->create();
        $adminRole = Role::query()->where('slug', UserType::ADMIN->value)->first();
        if (! $adminRole) {
            $adminRole = Role::query()->where('slug', 'manager')->first();
        }

        if ($adminRole) {
            $user->roles()->sync([$adminRole->id]);
        }

        Auth::guard('admin')->login($user);

        return redirect(route('admin.dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('admin')->logout();

        return redirect()->route('admin.login');
    }
}
