<?php

namespace App\Providers;

use App\Constants\Permissions;
use App\Models\Permission;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGates();
        app(\App\Services\SmtpSettingsService::class)->applySavedSettings();

        // Override config with Business Settings
        // We wrap this in a try-catch or check if table exists to avoid issues during migration
        try {
            if (Schema::hasTable('business_settings')) {
                $configuredBusinessName = trim((string) business_config('business_name', ''));
                $appName = $configuredBusinessName !== '' ? $configuredBusinessName : 'GymOS';

                config(['app.name' => $appName]);
            }
        } catch (\Throwable $e) {
            // Suppress errors during early bootstrap/migration
        }
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
        ? Password::min(12)
            ->mixedCase()
            ->letters()
            ->numbers()
            ->symbols()
            ->uncompromised()
        : null
        );
    }

    protected function configureGates(): void
    {
        // Super Admin Bypass
        Gate::before(function ($user, $ability) {
            return method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin() ? true : null;
        });

        // Panel Access
        Gate::define('access-admin-panel', fn (User $user) => $user->isAdmin() || $user->isSuperAdmin());
        Gate::define('access-customer-panel', fn (User $user) => $user->isCustomer());

        foreach (Permissions::getPermissionNames() as $permissionName) {
            Gate::define($permissionName, fn (User $user) => $user->hasPermission($permissionName));
        }

        // Dynamic Permissions
        try {
            if (Schema::hasTable('permissions')) {
                foreach (Permission::all() as $permission) {
                    Gate::define($permission->name, function (User $user) use ($permission) {
                        return $user->hasPermission($permission->name);
                    });
                }
            }
        } catch (\Exception $e) {
            // Ignored during migrations/setup
        }
    }
}
