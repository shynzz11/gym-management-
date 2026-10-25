<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\PlanAccessTypeRequest;
use App\Models\Plan;
use App\Models\PlanAccessType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PlanAccessTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('manage_access_types');

        $search = trim((string) $request->string('search'));

        return Inertia::render('Admin/Plans/AccessTypes/Index', [
            'accessTypes' => PlanAccessType::query()
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($accessTypeQuery) use ($search): void {
                        $accessTypeQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->withCount('plans')
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'totalAccessTypes' => PlanAccessType::query()->count(),
            'activeAccessTypesCount' => PlanAccessType::query()
                ->where('is_active', true)
                ->count(),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PlanAccessTypeRequest $request): RedirectResponse
    {
        Gate::authorize('manage_access_types');

        PlanAccessType::create($request->validated());

        return back()->with('success', 'Access type created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PlanAccessTypeRequest $request, PlanAccessType $accessType): RedirectResponse
    {
        Gate::authorize('manage_access_types');

        $accessType->update($request->validated());

        return back()->with('success', 'Access type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PlanAccessType $accessType): RedirectResponse
    {
        Gate::authorize('manage_access_types');

        if (PlanAccessType::query()->count() <= 1) {
            return back()->with('error', 'At least one access type must remain. Create another access type before deleting this one.');
        }

        // Check if access type is in use by any plans
        if (Plan::query()->where('access_type', $accessType->slug)->exists()) {
            return back()->with('error', 'Cannot delete this access type while it is assigned to one or more plans. Reassign those plans first.');
        }

        $accessType->delete();

        return back()->with('success', 'Access type deleted successfully.');
    }

    public function toggleActive(PlanAccessType $accessType): RedirectResponse
    {
        Gate::authorize('manage_access_types');

        if ($accessType->is_active && PlanAccessType::query()->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'At least one active access type is required.');
        }

        $accessType->update([
            'is_active' => ! $accessType->is_active,
        ]);

        $message = $accessType->fresh()->is_active ? 'Access type activated.' : 'Access type deactivated.';

        return back()->with('success', $message);
    }
}
