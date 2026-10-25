<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\Role;

class SupremeRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $method = $request->method();

        // Only care about modifications
        if (!in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        // Check for User protection
        $userParam = $request->route('user');
        if ($userParam) {
            $targetUser = $userParam instanceof User ? $userParam : User::find($userParam);
            if ($targetUser && $targetUser->isSuperAdmin()) {
                return abort(403, 'The Supreme Leader cannot be modified or removed.');
            }
        }

        // Check for Role protection
        $roleParam = $request->route('role');
        if ($roleParam) {
            $targetRole = $roleParam instanceof Role ? $roleParam : Role::find($roleParam);
            if ($targetRole && $targetRole->name === 'Super Admin') {
                return abort(403, 'The Super Admin role is immutable.');
            }
        }

        return $next($request);
    }
}