<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CustomerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check() || ! Auth::user()->isCustomer()) {
            abort(403, 'Unauthorized action.');
        }

        if (! Auth::user()->isActive()) {
            $warningMessage = Auth::user()->subscriptions()->exists()
                ? 'Your membership has expired. Please renew to continue enjoying full access.'
                : 'You do not have an active membership yet. Please purchase a membership to continue.';

            $request->session()->flash('warning', $warningMessage);
        }

        return $next($request);
    }
}
