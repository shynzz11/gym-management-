<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Permissions;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        Gate::authorize(Permissions::VIEW_REPORTS);

        $today = now()->startOfDay();
        $expiringSoonDayLimit = 7;
        $expiringSoonUntil = $today->copy()->addDays($expiringSoonDayLimit)->endOfDay();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $expiredMembers = Subscription::query()
            ->with(['user:id,name,email,type', 'plan:id,name'])
            ->whereHas('user', function ($query): void {
                $query->where('type', UserType::CUSTOMER->value);
            })
            ->whereDate('end_date', '<', $today->toDateString())
            ->orderByDesc('end_date')
            ->get()
            ->map(function (Subscription $subscription) use ($today): array {
                return [
                    'id' => $subscription->id,
                    'member_name' => $subscription->user?->name ?? 'Unknown',
                    'member_email' => $subscription->user?->email ?? '-',
                    'plan_name' => $subscription->plan?->name ?? 'N/A',
                    'status' => $subscription->status,
                    'start_date' => $subscription->start_date?->toDateString(),
                    'end_date' => $subscription->end_date?->toDateString(),
                    'days_overdue' => $subscription->end_date?->diffInDays($today) ?? 0,
                ];
            })
            ->values();

        $expiringSoonMembers = Subscription::query()
            ->with(['user:id,name,email,type', 'plan:id,name'])
            ->whereHas('user', function ($query): void {
                $query->where('type', UserType::CUSTOMER->value);
            })
            ->where('status', 'active')
            ->whereDate('end_date', '>=', $today->toDateString())
            ->whereDate('end_date', '<=', $expiringSoonUntil->toDateString())
            ->orderBy('end_date')
            ->get()
            ->map(function (Subscription $subscription) use ($today): array {
                return [
                    'id' => $subscription->id,
                    'member_name' => $subscription->user?->name ?? 'Unknown',
                    'member_email' => $subscription->user?->email ?? '-',
                    'plan_name' => $subscription->plan?->name ?? 'N/A',
                    'start_date' => $subscription->start_date?->toDateString(),
                    'end_date' => $subscription->end_date?->toDateString(),
                    'days_left' => $subscription->end_date ? max(0, (int) $today->diffInDays($subscription->end_date, false)) : 0,
                ];
            })
            ->values();

        $monthlyRenewalsQuery = Payment::query()
            ->with(['subscription.user:id,name,email,type', 'subscription.plan:id,name'])
            ->whereHas('subscription.user', function ($query): void {
                $query->where('type', UserType::CUSTOMER->value);
            })
            ->whereBetween('created_at', [$monthStart, $monthEnd]);

        if (Schema::hasColumn('payments', 'transaction_type')) {
            $monthlyRenewalsQuery->where('transaction_type', 'renew');
        } else {
            $monthlyRenewalsQuery->whereExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('payments as previous_payments')
                    ->whereColumn('previous_payments.subscription_id', 'payments.subscription_id')
                    ->whereColumn('previous_payments.id', '<', 'payments.id');
            });
        }

        $monthlyRenewals = $monthlyRenewalsQuery
            ->latest()
            ->get()
            ->map(function (Payment $payment): array {
                return [
                    'id' => $payment->id,
                    'member_name' => $payment->subscription?->user?->name ?? 'Unknown',
                    'member_email' => $payment->subscription?->user?->email ?? '-',
                    'plan_name' => $payment->subscription?->plan?->name ?? 'N/A',
                    'amount' => $payment->amount,
                    'method' => $payment->method,
                    'status' => $payment->status,
                    'transaction_type' => $payment->transaction_type ?? null,
                    'paid_at' => $payment->created_at?->toDateTimeString(),
                ];
            })
            ->values();

        $monthlyAddedPlans = Subscription::query()
            ->with(['user:id,name,email,type', 'plan:id,name'])
            ->whereHas('user', function ($query): void {
                $query->where('type', UserType::CUSTOMER->value);
            })
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->latest()
            ->get()
            ->map(function (Subscription $subscription): array {
                return [
                    'id' => $subscription->id,
                    'member_name' => $subscription->user?->name ?? 'Unknown',
                    'member_email' => $subscription->user?->email ?? '-',
                    'plan_name' => $subscription->plan?->name ?? 'N/A',
                    'status' => $subscription->status,
                    'start_date' => $subscription->start_date?->toDateString(),
                    'end_date' => $subscription->end_date?->toDateString(),
                    'added_at' => $subscription->created_at?->toDateTimeString(),
                ];
            })
            ->values();

        return inertia('Admin/Reports/Index', [
            'expiredMembers' => $expiredMembers,
            'expiringSoonMembers' => $expiringSoonMembers,
            'monthlyRenewals' => $monthlyRenewals,
            'monthlyAddedPlans' => $monthlyAddedPlans,
            'meta' => [
                'expiringSoonDayLimit' => $expiringSoonDayLimit,
                'monthLabel' => now()->format('F Y'),
            ],
        ]);
    }
}
