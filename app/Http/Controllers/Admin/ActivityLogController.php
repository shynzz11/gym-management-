<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Permissions;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function __construct(protected \App\Services\ActivityLogService $activityLogService) {}

    /**
     * Display activity logs
     */
    public function index(\App\Http\Requests\Admin\FilterActivityLogRequest $request): \Inertia\Response|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        Gate::authorize(Permissions::ACTIVITY_LOG);

        $filters = $request->validated();

        // Handle CSV Export
        if (($filters['export'] ?? null) === 'csv') {
            $fileName = 'activity_logs_' . date('Y-m-d_H-i-s') . '.csv';
            $query = $this->activityLogService->getFilteredQuery($filters);

            return response()->streamDownload(function() use($query) {
                $file = fopen('php://output', 'w');
                
                // Add CSV Header
                fputcsv($file, ['ID', 'User', 'Email', 'Event', 'Description', 'IP Address', 'User Agent', 'Date']);

                $query->chunk(100, function($logs) use($file) {
                    foreach ($logs as $log) {
                        fputcsv($file, [
                            $log->id,
                            $log->user?->name ?? 'System',
                            $log->user?->email ?? '-',
                            $log->event,
                            $log->description,
                            $log->ip_address,
                            $log->user_agent,
                            $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-'
                        ]);
                    }
                });

                fclose($file);
            }, $fileName, [
                "Content-type" => "text/csv",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            ]);
        }

        return Inertia::render('Admin/ActivityLog/Index', [
            'logs' => $this->activityLogService->getAll($filters),
            'stats' => $this->activityLogService->getStats(),
            'filters' => $filters,
        ]);
    }

    /**
     * Display a specific activity log entry
     */
    public function show(ActivityLog $activityLog): Response
    {
        Gate::authorize(Permissions::ACTIVITY_LOG);
        
        $activityLog->load('user:id,name,email');

        return Inertia::render('Admin/ActivityLog/Show', [
            'log' => $activityLog,
        ]);
    }
}
