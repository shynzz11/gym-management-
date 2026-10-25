<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Permissions;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        Gate::authorize(Permissions::DASHBOARD_VIEW);

        $totalMembers = User::where(fn ($q) => $q->where('type', \App\Enums\UserType::CUSTOMER))
            ->count();

        $totalRevenue = Payment::where(fn ($q) => $q->where('status', 'completed'))
            ->sum('amount');

        $expiringMembers = Subscription::query()
            ->with(['user.customerProfile'])
            ->where('status', 'active')
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays(5)->toDateString()])
            ->whereHas('user', fn ($query) => $query->where('type', UserType::CUSTOMER))
            ->orderBy('end_date')
            ->get()
            ->unique('user_id')
            ->map(fn (Subscription $subscription): array => [
                'id' => $subscription->user->id,
                'name' => $subscription->user->name,
                'phone' => $this->normalizeIndianPhoneNumber($subscription->user->customerProfile?->phone),
                'end_date' => $subscription->end_date->toDateString(),
            ])
            ->values();

        $expiringSoon = $expiringMembers->count();

        $recentTransactions = Payment::query()
            ->with(['subscription.user', 'subscription.plan'])
            ->latest()
            ->limit(10)
            ->get();

        $expiredSubscriptions = Subscription::with(['user', 'plan'])
            ->where(fn ($q) => $q->where('status', 'expired'))
            ->latest('end_date')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Pages/Dashboard', [
            'stats' => [
                'total_members' => $totalMembers,
                'total_revenue' => (float) $totalRevenue,
                'expiring_soon' => $expiringSoon,
            ],
            'recent_transactions' => $recentTransactions,
            'expired_subscriptions' => $expiredSubscriptions,
            'expiring_members' => $expiringMembers,
        ]);
    }

    private function normalizeIndianPhoneNumber(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '');
        if (! is_string($digits) || $digits === '') {
            return null;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return $digits;
        }

        $nationalNumber = ltrim($digits, '0');
        if (strlen($nationalNumber) !== 10) {
            return null;
        }

        return '91'.$nationalNumber;
    }
}
