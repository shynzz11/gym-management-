<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\Plan;
use App\Models\PlanAccessType;
use Illuminate\Support\Facades\DB;

class PlanService
{
    public function getAll()
    {
        return Plan::query()
            ->withCount('subscriptions')
            ->latest()
            ->get();
    }

    public function getActivePlansForRegistration(): array
    {
        return Plan::query()
            ->where('is_active', true)
            ->get()
            ->toArray();
    }

    protected function handleAccessTimes(array $data): array
    {
        if (isset($data['access_type'])) {
            $accessType = PlanAccessType::query()
                ->where('slug', $data['access_type'])
                ->first();

            if ($accessType) {
                if (! $accessType->is_anytime) {
                    $data['start_time'] = $accessType->start_time;
                    $data['end_time'] = $accessType->end_time;
                } else {
                    $data['start_time'] = null;
                    $data['end_time'] = null;
                }
            }
        }

        return $data;
    }

    protected function normalizeFacilities(array $data): array
    {
        $facilities = $data['facilities'] ?? [];

        if (! is_array($facilities)) {
            $data['facilities'] = [];

            return $data;
        }

        $normalizedFacilities = collect($facilities)
            ->filter(fn ($facility) => is_string($facility))
            ->map(fn (string $facility) => trim($facility))
            ->filter(fn (string $facility) => $facility !== '')
            ->unique(fn (string $facility) => mb_strtolower($facility))
            ->values();

        $facilityNameMap = Facility::query()
            ->get(['name'])
            ->pluck('name')
            ->mapWithKeys(fn (string $name) => [mb_strtolower($name) => $name]);

        $data['facilities'] = $normalizedFacilities
            ->map(fn (string $facility) => $facilityNameMap[mb_strtolower($facility)] ?? $facility)
            ->unique(fn (string $facilityName) => mb_strtolower($facilityName))
            ->values()
            ->all();

        return $data;
    }

    public function create(array $data): Plan
    {
        return DB::transaction(function () use ($data) {
            if (! empty($data['is_default'])) {
                Plan::query()->where('is_default', '=', true)->update(['is_default' => false]);
            }

            $data = $this->handleAccessTimes($data);
            $data = $this->normalizeFacilities($data);

            return Plan::create($data);
        });
    }

    public function update(Plan $plan, array $data): Plan
    {
        return DB::transaction(function () use ($plan, $data) {
            if (! empty($data['is_default'])) {
                Plan::query()->where('id', '!=', $plan->id)
                    ->where('is_default', '=', true)
                    ->update(['is_default' => false]);
            }

            $data = $this->handleAccessTimes($data);
            $data = $this->normalizeFacilities($data);

            $plan->update($data);

            return $plan->fresh();
        });
    }

    public function toggleActive(Plan $plan): Plan
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return $plan->fresh();
    }

    public function delete(Plan $plan): void
    {
        $plan->delete();
    }
}
