<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StoreFacilityRequest;
use App\Http\Requests\Plan\UpdateFacilityRequest;
use App\Models\Facility;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Response;

class PlanFacilityController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('manage_facilities');

        $search = trim((string) $request->string('search'));
        $usageCounts = $this->facilityUsageCounts();

        $facilities = Facility::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($facilityQuery) use ($search): void {
                    $facilityQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(function (Facility $facility) use ($usageCounts): array {
                return [
                    'id' => $facility->id,
                    'name' => $facility->name,
                    'description' => $facility->description,
                    'is_default' => $facility->is_default,
                    'plans_count' => $usageCounts[Str::lower($facility->name)] ?? 0,
                    'created_at' => $facility->created_at?->toIso8601String(),
                    'updated_at' => $facility->updated_at?->toIso8601String(),
                ];
            });

        return inertia('Admin/Plans/Facilities/Index', [
            'facilities' => $facilities,
            'totalFacilities' => Facility::query()->count(),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(StoreFacilityRequest $request): RedirectResponse
    {
        Gate::authorize('manage_facilities');

        Facility::query()->create($request->validated());

        return back()->with('success', 'Facility created successfully.');
    }

    public function update(UpdateFacilityRequest $request, Facility $facility): RedirectResponse
    {
        Gate::authorize('manage_facilities');

        if ($facility->is_default) {
            return back()->with('error', 'Default facility cannot be edited.');
        }

        $validatedData = $request->validated();
        $oldName = $facility->name;
        $newName = $validatedData['name'];

        DB::transaction(function () use ($facility, $validatedData, $oldName, $newName): void {
            $facility->update($validatedData);

            if (Str::lower($oldName) !== Str::lower($newName)) {
                $this->renameFacilityOnPlans($oldName, $newName);
            }
        });

        return back()->with('success', 'Facility updated successfully.');
    }

    public function destroy(Facility $facility): RedirectResponse
    {
        Gate::authorize('manage_facilities');

        if ($facility->is_default) {
            return back()->with('error', 'Default facility cannot be deleted.');
        }

        if ($this->isFacilityInUse($facility->name)) {
            return back()->with('error', 'Cannot delete this facility while it is assigned to one or more plans.');
        }

        $facility->delete();

        return back()->with('success', 'Facility deleted successfully.');
    }

    /**
     * @return array<string, int>
     */
    protected function facilityUsageCounts(): array
    {
        $usageCounts = [];

        Plan::query()
            ->get(['facilities'])
            ->each(function (Plan $plan) use (&$usageCounts): void {
                collect($plan->facilities ?? [])
                    ->filter(fn (mixed $facilityName): bool => is_string($facilityName))
                    ->map(fn (string $facilityName): string => trim($facilityName))
                    ->filter(fn (string $facilityName): bool => $facilityName !== '')
                    ->unique(fn (string $facilityName): string => Str::lower($facilityName))
                    ->each(function (string $facilityName) use (&$usageCounts): void {
                        $key = Str::lower($facilityName);
                        $usageCounts[$key] = ($usageCounts[$key] ?? 0) + 1;
                    });
            });

        return $usageCounts;
    }

    protected function isFacilityInUse(string $facilityName): bool
    {
        $facilityNameKey = Str::lower(trim($facilityName));

        return Plan::query()
            ->get(['facilities'])
            ->contains(function (Plan $plan) use ($facilityNameKey): bool {
                return collect($plan->facilities ?? [])
                    ->filter(fn (mixed $value): bool => is_string($value))
                    ->contains(fn (string $value): bool => Str::lower(trim($value)) === $facilityNameKey);
            });
    }

    protected function renameFacilityOnPlans(string $oldName, string $newName): void
    {
        $oldNameKey = Str::lower(trim($oldName));
        $newNameValue = trim($newName);

        Plan::query()
            ->get(['id', 'facilities'])
            ->each(function (Plan $plan) use ($oldNameKey, $newNameValue): void {
                $currentFacilities = is_array($plan->facilities) ? $plan->facilities : [];
                $hasMatch = false;

                $nextFacilities = collect($currentFacilities)
                    ->map(function (mixed $facility) use ($oldNameKey, $newNameValue, &$hasMatch): mixed {
                        if (! is_string($facility)) {
                            return $facility;
                        }

                        if (Str::lower(trim($facility)) !== $oldNameKey) {
                            return $facility;
                        }

                        $hasMatch = true;

                        return $newNameValue;
                    })
                    ->filter(fn (mixed $facility): bool => is_string($facility))
                    ->map(fn (string $facility): string => trim($facility))
                    ->filter(fn (string $facility): bool => $facility !== '')
                    ->unique(fn (string $facility): string => Str::lower($facility))
                    ->values()
                    ->all();

                if ($hasMatch) {
                    $plan->update(['facilities' => $nextFacilities]);
                }
            });
    }
}
