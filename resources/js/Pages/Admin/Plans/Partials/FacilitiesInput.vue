<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Check, ChevronDown, Plus, Search, X } from 'lucide-vue-next';

interface FacilityOption {
    id?: number;
    name: string;
    description?: string | null;
}

const props = defineProps<{
    modelValue: string[];
    availableFacilities?: FacilityOption[];
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string[]): void;
}>();

const rootRef = ref<HTMLElement | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);
const searchTerm = ref('');
const dropdownOpen = ref(false);
const invalidSearchAttempt = ref(false);
const facilityCatalog = ref<FacilityOption[]>([]);

const normalizeFacilities = (facilities: string[]): string[] => {
    return facilities
        .map((facility) => facility.trim())
        .filter((facility) => facility.length > 0)
        .filter((facility, index, allFacilities) => {
            return allFacilities.findIndex((item) => item.toLowerCase() === facility.toLowerCase()) === index;
        });
};

const selectedFacilities = ref<string[]>(normalizeFacilities(props.modelValue ?? []));

const buildCatalog = (selected: string[]): FacilityOption[] => {
    const availableCatalog = Array.isArray(props.availableFacilities) ? props.availableFacilities : [];
    const catalogNames = new Set(availableCatalog.map((facility) => facility.name.toLowerCase()));
    const selectedNotInCatalog = selected
        .filter((facilityName) => !catalogNames.has(facilityName.toLowerCase()))
        .map((facilityName) => ({ name: facilityName, description: null }));

    return [...availableCatalog, ...selectedNotInCatalog]
        .filter((facility) => facility.name.trim() !== '')
        .filter((facility, index, allFacilities) => {
            return allFacilities.findIndex((item) => item.name.toLowerCase() === facility.name.toLowerCase()) === index;
        })
        .sort((a, b) => a.name.localeCompare(b.name));
};

watch(
    () => props.modelValue,
    (value) => {
        selectedFacilities.value = normalizeFacilities(Array.isArray(value) ? value : []);
        facilityCatalog.value = buildCatalog(selectedFacilities.value);
    },
    { immediate: true },
);

watch(
    () => props.availableFacilities,
    () => {
        facilityCatalog.value = buildCatalog(selectedFacilities.value);
    },
    { immediate: true, deep: true },
);

const filteredFacilities = computed(() => {
    const keyword = searchTerm.value.trim().toLowerCase();

    if (keyword === '') {
        return facilityCatalog.value;
    }

    return facilityCatalog.value.filter((facility) => {
        const facilityName = facility.name.toLowerCase();
        const facilityDescription = (facility.description ?? '').toLowerCase();

        return facilityName.includes(keyword) || facilityDescription.includes(keyword);
    });
});

const hasSearchTerm = computed(() => searchTerm.value.trim() !== '');

const hasExactMatch = computed(() => {
    const keyword = searchTerm.value.trim().toLowerCase();

    if (keyword === '') {
        return true;
    }

    return facilityCatalog.value.some((facility) => facility.name.toLowerCase() === keyword);
});

const canCreateFromSearch = computed(() => {
    return hasSearchTerm.value && !hasExactMatch.value;
});

const updateFacilities = (nextFacilities: string[]): void => {
    const normalizedFacilities = normalizeFacilities(nextFacilities);
    selectedFacilities.value = normalizedFacilities;
    facilityCatalog.value = buildCatalog(normalizedFacilities);
    emit('update:modelValue', normalizedFacilities);
};

const isSelected = (facilityName: string): boolean => {
    return selectedFacilities.value.some((item) => item.toLowerCase() === facilityName.toLowerCase());
};

const openDropdown = (): void => {
    dropdownOpen.value = true;
};

const closeDropdown = (): void => {
    dropdownOpen.value = false;
};

const toggleFacility = (facilityName: string): void => {
    if (isSelected(facilityName)) {
        updateFacilities(selectedFacilities.value.filter((item) => item.toLowerCase() !== facilityName.toLowerCase()));
    } else {
        updateFacilities([...selectedFacilities.value, facilityName]);
    }

    openDropdown();
    searchInputRef.value?.focus();
};

const removeSelectedFacility = (facilityName: string): void => {
    updateFacilities(selectedFacilities.value.filter((item) => item.toLowerCase() !== facilityName.toLowerCase()));
};

const triggerInvalidInputAnimation = (): void => {
    invalidSearchAttempt.value = true;
    searchInputRef.value?.focus();
    setTimeout(() => {
        invalidSearchAttempt.value = false;
    }, 350);
};

const createFacilityFromName = (rawName: string): void => {
    const facilityName = rawName.trim();

    if (facilityName === '') {
        triggerInvalidInputAnimation();

        return;
    }

    const existsInCatalog = facilityCatalog.value.some((facility) => facility.name.toLowerCase() === facilityName.toLowerCase());

    if (!existsInCatalog) {
        facilityCatalog.value = [...facilityCatalog.value, { name: facilityName, description: null }].sort((a, b) => a.name.localeCompare(b.name));
    }

    if (!isSelected(facilityName)) {
        updateFacilities([...selectedFacilities.value, facilityName]);
    }

    searchTerm.value = '';
    openDropdown();
    searchInputRef.value?.focus();
};

const handleEnter = (): void => {
    if (canCreateFromSearch.value) {
        createFacilityFromName(searchTerm.value);

        return;
    }

    if (filteredFacilities.value.length === 1) {
        toggleFacility(filteredFacilities.value[0].name);
    }
};

const handleClickOutside = (event: MouseEvent): void => {
    const target = event.target as Node | null;

    if (!rootRef.value || !target) {
        return;
    }

    if (!rootRef.value.contains(target)) {
        closeDropdown();
    }
};

onMounted(() => {
    window.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
    window.removeEventListener('mousedown', handleClickOutside);
});
</script>

<template>
    <div ref="rootRef" class="relative">
        <div
            class="flex min-h-12 w-full flex-wrap items-center gap-2 rounded-2xl border bg-white px-3 py-2 transition-all"
            :class="
                invalidSearchAttempt
                    ? 'input-shake border-red-300 ring-2 ring-red-200/70'
                    : 'border-slate-200 focus-within:border-primary-500 focus-within:ring-4 focus-within:ring-primary-500/10'
            "
            @click="openDropdown"
        >
            <span
                v-for="facility in selectedFacilities"
                :key="facility"
                class="inline-flex h-7 items-center gap-1.5 rounded-full border border-primary-200 bg-primary-50 px-2.5 text-xs font-semibold text-primary-700"
            >
                {{ facility }}
                <button
                    type="button"
                    class="inline-flex h-4 w-4 items-center justify-center rounded-full text-primary-700 transition-colors hover:bg-primary-100"
                    @click.stop="removeSelectedFacility(facility)"
                >
                    <X :size="11" />
                </button>
            </span>

            <div class="relative flex min-w-[170px] flex-1 items-center">
                <Search :size="14" class="pointer-events-none absolute left-1 text-slate-400" />
                <input
                    ref="searchInputRef"
                    v-model="searchTerm"
                    type="text"
                    class="h-8 w-full bg-transparent pl-7 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none"
                    :placeholder="selectedFacilities.length === 0 ? __('Search and select facilities...') : __('Search more facilities...')"
                    @focus="openDropdown"
                    @keydown.enter.prevent="handleEnter"
                />
            </div>

            <button
                type="button"
                class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"
                @click.stop="dropdownOpen = !dropdownOpen"
            >
                <ChevronDown :size="16" class="transition-transform duration-200" :class="dropdownOpen ? 'rotate-180' : ''" />
            </button>
        </div>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="translate-y-1 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-1 opacity-0"
        >
            <div
                v-if="dropdownOpen"
                class="absolute z-40 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
            >
                <div class="max-h-64 overflow-y-auto p-1.5">
                    <button
                        v-for="facility in filteredFacilities"
                        :key="facility.id ?? facility.name"
                        type="button"
                        class="flex w-full items-start justify-between rounded-xl px-3 py-2 text-left transition-colors hover:bg-primary-50"
                        @click="toggleFacility(facility.name)"
                    >
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ facility.name }}</p>
                            <p class="text-xs text-slate-500">{{ facility.description || __('Custom option') }}</p>
                        </div>
                        <span
                            class="ms-2 mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-md border"
                            :class="isSelected(facility.name) ? 'border-primary-600 bg-primary-600 text-white' : 'border-slate-300 text-transparent'"
                        >
                            <Check :size="12" />
                        </span>
                    </button>

                    <button
                        v-if="canCreateFromSearch"
                        type="button"
                        class="mt-1 flex w-full items-center gap-2 rounded-xl border border-dashed border-primary-300 bg-primary-50/70 px-3 py-2 text-left text-sm font-semibold text-primary-700 transition-colors hover:bg-primary-100"
                        @click="createFacilityFromName(searchTerm)"
                    >
                        <Plus :size="14" />
                        {{ __('Create facility') }} "{{ searchTerm.trim() }}"
                    </button>

                    <div
                        v-if="filteredFacilities.length === 0 && !canCreateFromSearch"
                        class="rounded-xl px-3 py-5 text-center text-sm text-slate-500"
                    >
                        {{ __('No facilities found.') }}
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.input-shake {
    animation: shake-x 0.35s ease-in-out;
}

@keyframes shake-x {
    0%,
    100% {
        transform: translateX(0);
    }
    20% {
        transform: translateX(-4px);
    }
    40% {
        transform: translateX(4px);
    }
    60% {
        transform: translateX(-3px);
    }
    80% {
        transform: translateX(3px);
    }
}
</style>
