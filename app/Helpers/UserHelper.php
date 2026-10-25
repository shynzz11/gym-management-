<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserHelper
{
    /**
     * Get the target user (current or provided).
     */
    protected static function getUser($user = null): ?User
    {
        if ($user instanceof User) {
            return $user;
        }
        return Auth::user();
    }

    public static function isSuperAdmin($user = null): bool
    {
        $user = static::getUser($user);
        return $user ? $user->isSuperAdmin() : false;
    }

    public static function isAdmin($user = null): bool
    {
        $user = static::getUser($user);
        return $user ? $user->isAdmin() : false;
    }

    public static function isCustomer($user = null): bool
    {
        $user = static::getUser($user);
        return $user ? $user->isCustomer() : false;
    }

    public static function hasPermission(string $permission, $user = null): bool
    {
        $user = static::getUser($user);
        return $user ? $user->hasPermission($permission) : false;
    }
}
