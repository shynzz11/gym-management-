<script setup lang="ts">
import { suggestedPlanFacilitiesList } from '@/lib/planFacilities';
import { CircleCheckBig, CircleX } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    features?: string[] | null;
    allFeatures?: string[];
    showOnlyIncluded?: boolean;
}>();

const normalize = (value: string): string => value.trim().toLowerCase();

const selectedFacilities = computed(() => {
    const facilities = Array.isArray(props.features) ? props.features : [];

    return facilities
        .map((facility) => facility.trim())
        .filter((facility) => facility.length > 0)
        .filter((facility, index, allFacilities) => {
            return allFacilities.findIndex((item) => normalize(item) === normalize(facility)) === index;
        });
});

const baselineFacilities = computed(() => {
    const list = (props.allFeatures ?? suggestedPlanFacilitiesList).map((facility) => facility.trim()).filter((facility) => facility.length > 0);

    return list.filter((facility, index, allFacilities) => {
        return allFacilities.findIndex((item) => normalize(item) === normalize(facility)) === index;
    });
});

const featureRows = computed(() => {
    const selectedSet = new Set(selectedFacilities.value.map((facility) => normalize(facility)));
    const extraFacilities = selectedFacilities.value.filter((facility) => {
        return !baselineFacilities.value.some((item) => normalize(item) === normalize(facility));
    });
    const rows = [...baselineFacilities.value, ...extraFacilities];

    return rows.map((facility) => ({
        name: facility,
        included: selectedSet.has(normalize(facility)),
    }));
});

const displayRows = computed(() => {
    if (props.showOnlyIncluded) {
        return featureRows.value.filter((feature) => feature.included);
    }

    return featureRows.value;
});
</script>

<template>
    <ul class="space-y-1.5">
        <li
            v-for="feature in displayRows"
            :key="feature.name"
            class="flex items-center gap-2 text-sm font-medium leading-none"
            :class="feature.included ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-400 dark:text-slate-500'"
        >
            <CircleCheckBig v-if="feature.included" :size="18" class="shrink-0 text-emerald-500" />
            <CircleX v-else :size="18" class="shrink-0 text-slate-400 dark:text-slate-500" />
            <span>{{ feature.name }}</span>
        </li>
    </ul>
</template>
