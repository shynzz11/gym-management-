<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogService
{
    public function getAll(array $filters = [], int $perPage = 20)
    {
        return $this->getFilteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    public function getFilteredQuery(array $filters = [])
    {
        $query = ActivityLog::with('user:id,name,email')->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($q) => 
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if (!empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (!empty($filters['date_range'])) {
            $query->when($filters['date_range'] === 'today', fn($q) => 
                $q->whereDate('created_at', today()))
            ->when($filters['date_range'] === 'week', fn($q) => 
                $q->where('created_at', '>=', now()->subWeek()))
            ->when($filters['date_range'] === 'month', fn($q) => 
                $q->where('created_at', '>=', now()->subMonth()));
        }

        return $query;
    }

    public function getStats(): array
    {
        return [
            'total_events' => ActivityLog::count(),
            'today_events' => ActivityLog::whereDate('created_at', today())->count(),
            'security_events' => ActivityLog::whereIn('event', ['login', 'logout', 'failed_login', 'deleted'])->count(),
            'critical_alerts' => ActivityLog::where('event', 'failed_login')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
        ];
    }

    public function getLatest(int $limit = 20)
    {
        return ActivityLog::with('user')->latest()->limit($limit)->get();
    }
}
