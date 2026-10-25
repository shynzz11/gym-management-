<?php

namespace App\Http\Middleware;

use App\Models\PlanAccessType;
use App\Models\Subscription;
use App\Notifications\MembershipExpired;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class SyncMembership
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
            $expiredSubscriptions = Subscription::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->whereDate('end_date', '<', now()->toDateString())
                ->get();

            foreach ($expiredSubscriptions as $subscription) {
                $subscription->update(['status' => 'expired']);

                $alreadyNotified = $user->notifications()
                    ->where('type', MembershipExpired::class)
                    ->where('data->subscription_id', $subscription->id)
                    ->exists();

                if (! $alreadyNotified) {
                    $user->notify(new MembershipExpired($subscription));
                }
            }

            if ($expiredSubscriptions->isNotEmpty()) {
                $request->session()->flash('warning', 'Your membership has expired. Please renew to restore full access.');
            }

            $activeSubscriptions = $user->activeSubscriptions()
                ->with('plan')
                ->get();

            if ($activeSubscriptions->isNotEmpty() && ! $this->isAnySubscriptionActiveNow($activeSubscriptions)) {
                $request->session()->flash('access_warning', 'Your active plans are currently outside their allowed access window.');
            }
        }

        return $next($request);
    }

    private function isAnySubscriptionActiveNow(Collection $subscriptions): bool
    {
        $accessTypes = PlanAccessType::query()
            ->whereIn('slug', $subscriptions->pluck('plan.access_type')->filter()->unique())
            ->get()
            ->keyBy('slug');

        $now = now();

        return $subscriptions->contains(function (Subscription $subscription) use ($accessTypes, $now): bool {
            $plan = $subscription->plan;

            if (! $plan) {
                return false;
            }

            $accessType = $accessTypes->get($plan->access_type);

            if (! $accessType || $accessType->is_anytime) {
                return true;
            }

            $startTime = $this->parseMembershipTime($plan->start_time ?? $accessType->start_time);
            $endTime = $this->parseMembershipTime($plan->end_time ?? $accessType->end_time);

            if (! $startTime || ! $endTime) {
                return true;
            }

            return $this->isWithinAccessWindow($now, $startTime, $endTime);
        });
    }

    private function parseMembershipTime(?string $time): ?Carbon
    {
        if (! is_string($time) || trim($time) === '') {
            return null;
        }

        $normalizedTime = trim($time);

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                $parsedTime = Carbon::createFromFormat($format, $normalizedTime);

                return now()->copy()->setTime(
                    $parsedTime->hour,
                    $parsedTime->minute,
                    $parsedTime->second,
                );
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            $parsedTime = Carbon::parse($normalizedTime);

            return now()->copy()->setTime(
                $parsedTime->hour,
                $parsedTime->minute,
                $parsedTime->second,
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function isWithinAccessWindow(Carbon $now, Carbon $startTime, Carbon $endTime): bool
    {
        if ($startTime->lessThanOrEqualTo($endTime)) {
            return $now->between($startTime, $endTime, true);
        }

        return $now->greaterThanOrEqualTo($startTime) || $now->lessThanOrEqualTo($endTime);
    }
}
