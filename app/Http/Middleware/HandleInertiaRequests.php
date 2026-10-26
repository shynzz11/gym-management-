<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // If on admin routes and not authenticated via default guard, try admin guard
        if (! $user && ($request->is('admin') || $request->is('admin/*'))) {
            $user = \Illuminate\Support\Facades\Auth::guard('admin')->user();
        }

        $permissions = [];

        if ($user) {
            if ($user->isSuperAdmin()) {
                $permissions = ['*'];
            } else {
                // Direct query to bypass Eloquent relationship loading issues
                $permissions = \Illuminate\Support\Facades\DB::table('user_roles')
                    ->join('role_permissions', 'user_roles.role_id', '=', 'role_permissions.role_id')
                    ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                    ->where('user_roles.user_id', $user->id)
                    ->distinct()
                    ->pluck('permissions.name')
                    ->all();
            }
        }

        $unreadCount = 0;
        if ($user) {
            try {
                $unreadCount = \Illuminate\Support\Facades\App::make(\App\Services\NotificationService::class)->getUnreadCount($user->id);
            } catch (\Exception $e) {
                $unreadCount = 0;
            }
        }

        $businessSettings = \App\Models\BusinessSetting::all()->pluck('value', 'key')->toArray();
        $businessName = trim((string) ($businessSettings['business_name'] ?? ''));

        if ($businessName === '') {
            $businessName = config('app.name');
        }

        return [
            ...parent::share($request),
            'name' => $businessName,
            'is_demo_mode' => env('APP_MODE') === 'demo',
            'auth' => [
                'user' => $user,
                'is_super_admin' => $user?->isSuperAdmin() ?? false,
                'unread_count' => $unreadCount,
                'permissions' => $permissions,
                'available_permissions' => collect(\App\Constants\Permissions::getAll())->map(function ($section) {
                    if (isset($section['sub_modules'])) {
                        $section['sub_modules'] = collect($section['sub_modules'])->map(function ($module) {
                            if (isset($module['route'])) {
                                try {
                                    $module['url'] = route($module['route']);
                                } catch (\Exception $e) {
                                    $module['url'] = '#';
                                }
                            }

                            return $module;
                        })->toArray();
                    }

                    return $section;
                })->toArray(),
            ],
            'branding' => array_merge(config('branding.theme'), [
                'business_settings' => $businessSettings,
            ]),
            'locale' => app()->getLocale(),
            'translations' => file_exists(lang_path(app()->getLocale().'.json'))
                ? json_decode(file_get_contents(lang_path(app()->getLocale().'.json')), true)
                : [],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('admin_warning'),
            ],
        ];
    }
}
