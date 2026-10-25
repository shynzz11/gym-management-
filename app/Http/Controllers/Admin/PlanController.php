<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Models\Facility;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Support\Facades\Gate;

class PlanController extends Controller
{
    public function __construct(protected PlanService $planService) {}

    public function index(): \Inertia\Response
    {
        Gate::authorize('manage_plans');

        return inertia('Admin/Plans/Index', [
            'plans' => $this->planService->getAll(),
            'accessTypes' => \App\Models\PlanAccessType::query()
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
            'facilities' => Facility::query()
                ->orderBy('name')
                ->get(['id', 'name', 'description']),
        ]);
    }

    public function store(StorePlanRequest $request): \Illuminate\Http\RedirectResponse
    {
        Gate::authorize('manage_plans');

        $validatedData = $request->validated();
        $validatedData['facilities'] = is_array($validatedData['facilities'] ?? null) ? $validatedData['facilities'] : [];

        $this->planService->create($validatedData);

        return to_route('admin.plans.index')->with('success', 'Plan created successfully.');
    }

    public function update(UpdatePlanRequest $request, Plan $plan): \Illuminate\Http\RedirectResponse
    {
        Gate::authorize('manage_plans');

        $validatedData = $request->validated();
        $validatedData['facilities'] = is_array($validatedData['facilities'] ?? null) ? $validatedData['facilities'] : [];

        $this->planService->update($plan, $validatedData);

        return to_route('admin.plans.index')->with('success', 'Plan updated successfully.');
    }

    public function toggleActive(Plan $plan): \Illuminate\Http\RedirectResponse
    {
        Gate::authorize('manage_plans');

        if ($plan->is_default) {
            return back()->with('error', 'The default plan cannot be deactivated.');
        }

        $this->planService->toggleActive($plan);

        $message = $plan->fresh()->is_active ? 'Plan activated.' : 'Plan deactivated.';

        return back()->with('success', $message);
    }

    public function destroy(Plan $plan): \Illuminate\Http\RedirectResponse
    {
        Gate::authorize('manage_plans');

        if ($plan->is_default) {
            return back()->with('error', 'The default plan cannot be deleted.');
        }

        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'Cannot delete a plan with existing subscribers. Deactivate it instead.');
        }

        $this->planService->delete($plan);

        return to_route('admin.plans.index')->with('success', 'Plan deleted successfully.');
    }
}
