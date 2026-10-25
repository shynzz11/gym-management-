<?php

namespace App\Http\Middleware;

use App\Notifications\MembershipExpired;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMembershipExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isCustomer()) {
            $subscription = $user->activeSubscription();

            if ($subscription && $subscription->end_date < now() && $subscription->status === 'active') {
                $subscription->update(['status' => 'expired']);

                // Flash message to the session
                $request->session()->flash('warning', 'Your session has updated: Membership Expired.');

                // Check if already notified to prevent duplicates
                $alreadyNotified = $user->notifications()
                    ->where('type', MembershipExpired::class)
                    ->where('data->subscription_id', $subscription->id)
                    ->exists();

                if (! $alreadyNotified) {
                    $user->notify(new MembershipExpired($subscription));
                }
            }
        }

        return $next($request);
    }
}
