<script setup lang="ts">
import FacilitiesInput from '@/Pages/Admin/Plans/Partials/FacilitiesInput.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, PropType, ref, watch } from 'vue';
import { ChevronDown, Info, Search, Tag, X } from 'lucide-vue-next';
import admin from '@/routes/admin';
import useCurrency from '@/Composables/useCurrency';

const props = defineProps({
    show: Boolean,
    plan: Object as PropType<any>,
    accessTypes: Array as PropType<any[]>,
    facilities: Array as PropType<any[]>,
});

const emit = defineEmits(['close']);

const activeTab = ref('basic');
const accessTypeDropdownRootRef = ref<HTMLElement | null>(null);
const accessTypeSearchInputRef = ref<HTMLInputElement | null>(null);
const accessTypeDropdownOpen = ref(false);
const accessTypeSearchTerm = ref('');

const getSelectableAccessTypes = (plan: any = props.plan): any[] => {
    const allAccessTypes = Array.isArray(props.accessTypes) ? props.accessTypes : [];
    const planAccessType = plan?.access_type;

    return allAccessTypes.filter((accessType) => {
        return Boolean(accessType?.is_active) || accessType?.slug === planAccessType;
    });
};

const selectableAccessTypes = computed(() => getSelectableAccessTypes());
const filteredSelectableAccessTypes = computed(() => {
    const keyword = accessTypeSearchTerm.value.trim().toLowerCase();

    if (keyword === '') {
        return selectableAccessTypes.value;
    }

    return selectableAccessTypes.value.filter((type) => {
        const typeName = String(type?.name ?? '').toLowerCase();
        const typeSlug = String(type?.slug ?? '').toLowerCase();
        const typeDescription = String(type?.description ?? '').toLowerCase();

        return typeName.includes(keyword) || typeSlug.includes(keyword) || typeDescription.includes(keyword);
    });
});

const resolveDefaultAccessType = (plan: any = props.plan): string => {
    return getSelectableAccessTypes(plan)?.[0]?.slug ?? '';
};

const toLocalDatetime = (utcString: string | null | undefined): string => {
    if (!utcString) {
        return '';
    }
    const date = new Date(utcString);
    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const pad = (n: number) => n.toString().padStart(2, '0');
    const year = date.getFullYear();
    const month = pad(date.getMonth() + 1);
    const day = pad(date.getDate());
    const hours = pad(date.getHours());
    const minutes = pad(date.getMinutes());

    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const toUTCISO = (localString: string | null | undefined): string | null => {
    if (!localString) {
        return null;
    }
    const date = new Date(localString);
    return Number.isNaN(date.getTime()) ? null : date.toISOString();
};

const form = useForm({
    name: '',
    price: '' as string | number,
    duration_days: '' as string | number,
    description: '',
    facilities: [] as string[],
    is_active: true,
    is_default: false,
    access_type: resolveDefaultAccessType(),
    is_offer: false,
    offer_price: '' as string | number,
    offer_ends_at: '',
});

watch(
    () => props.plan,
    (plan) => {
        if (plan) {
            form.name = plan.name;
            form.price = plan.price;
            form.duration_days = plan.duration_days;
            form.description = plan.description || '';
            form.facilities = Array.isArray(plan.facilities) ? [...plan.facilities] : [];
            form.is_active = !!plan.is_active;
            form.is_default = !!plan.is_default;
            const hasAssignedAccessType = getSelectableAccessTypes(plan).some((type) => type.slug === plan.access_type);
            form.access_type = hasAssignedAccessType ? plan.access_type : resolveDefaultAccessType();
            form.is_offer = !!plan.is_offer;
            form.offer_price = plan.offer_price || '';
            form.offer_ends_at = plan.offer_ends_at ? toLocalDatetime(plan.offer_ends_at) : '';
        } else {
            form.reset();
            form.is_active = true;
            form.is_default = false;
            form.access_type = resolveDefaultAccessType();
            activeTab.value = 'basic';
        }
    },
    { immediate: true },
);

watch(
    selectableAccessTypes,
    (types) => {
        if (types.length === 0) {
            form.access_type = '';
            accessTypeDropdownOpen.value = false;

            return;
        }

        const hasSelectedAccessType = types.some((type) => type.slug === form.access_type);

        if (!hasSelectedAccessType) {
            form.access_type = types[0].slug;
        }
    },
    { immediate: true },
);

const openAccessTypeDropdown = (): void => {
    if (selectableAccessTypes.value.length === 0) {
        return;
    }

    accessTypeDropdownOpen.value = true;

    requestAnimationFrame(() => {
        accessTypeSearchInputRef.value?.focus();
    });
};

const closeAccessTypeDropdown = (): void => {
    accessTypeDropdownOpen.value = false;
    accessTypeSearchTerm.value = '';
};

const selectAccessType = (slug: string): void => {
    form.access_type = slug;
    closeAccessTypeDropdown();
};

const handleClickOutside = (event: MouseEvent): void => {
    const target = event.target as Node | null;

    if (!target || !accessTypeDropdownRootRef.value) {
        return;
    }

    if (!accessTypeDropdownRootRef.value.contains(target)) {
        closeAccessTypeDropdown();
    }
};

onMounted(() => {
    window.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
    window.removeEventListener('mousedown', handleClickOutside);
});

watch(
    () => props.show,
    (show) => {
        if (!show) {
            closeAccessTypeDropdown();
        }
    },
);

const submit = () => {
    const transformPayload = (data: any) => ({
        ...data,
        offer_price: data.is_offer ? data.offer_price : null,
        offer_ends_at: data.is_offer ? toUTCISO(data.offer_ends_at) : null,
    });

    if (props.plan) {
        form.transform(transformPayload).put(admin.plans.update.url(props.plan.id), {
            onSuccess: () => emit('close'),
        });
    } else {
        form.transform(transformPayload).post(admin.plans.store.url(), {
            onSuccess: () => emit('close'),
        });
    }
};

const selectedAccessType = computed(() => props.accessTypes?.find((type) => type.slug === form.access_type));

const formatAccessTime = (time?: string | null): string => {
    if (!time) {
        return '--:--';
    }

    const [hourString = '0', minuteString = '00'] = time.split(':');
    const hour = Number(hourString);
    const period = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;

    return `${hour12}:${minuteString} ${period}`;
};

const facilityError = computed(() => {
    const errors = form.errors as Record<string, string>;
    const keyedFacilityError = Object.keys(errors).find((key) => key.startsWith('facilities.'));

    if (keyedFacilityError) {
        return errors[keyedFacilityError];
    }

    return errors.facilities;
});

const hasErrors = computed(() => Object.keys(form.errors).length > 0);

const { currencyPrefix } = useCurrency();
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[150] flex items-center justify-center p-4 sm:p-6">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="emit('close')"></div>

        <!-- Modal Content -->
        <div
            class="relative w-full max-w-2xl overflow-hidden rounded-[2.5rem] border border-slate-200 bg-white shadow-2xl transition-all dark:border-slate-800 dark:bg-slate-900"
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-100 px-8 py-6 dark:border-slate-800">
                <div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                        {{ plan ? __('Precision Edit') : __('Create Membership Plan') }}
                    </h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ plan ? __('Refine your membership parameters.') : __('Configure high-performance membership logic.') }}
                    </p>
                </div>
                <button
                    @click="emit('close')"
                    class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:bg-slate-800 dark:text-slate-500 dark:hover:bg-slate-700 dark:hover:text-slate-300"
                >
                    <X :size="20" />
                </button>
            </div>

            <!-- Tabs Navigation -->
            <div class="flex gap-1 border-b border-slate-100 bg-slate-50/50 px-8 dark:border-slate-800 dark:bg-slate-800/30">
                <button
                    v-for="tab in ['basic', 'promotion']"
                    :key="tab"
                    @click="activeTab = tab"
                    class="relative px-4 py-4 text-sm font-bold transition-all"
                    :class="
                        activeTab === tab
                            ? 'text-primary-600 dark:text-primary-400'
                            : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'
                    "
                >
                    <div class="flex items-center gap-2">
                        <Info v-if="tab === 'basic'" :size="16" />
                        <Tag v-if="tab === 'promotion'" :size="16" />
                        {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
                    </div>
                    <div v-if="activeTab === tab" class="absolute bottom-0 left-0 h-0.5 w-full bg-primary-600 dark:bg-primary-400"></div>
                </button>
            </div>

            <!-- Body -->
            <form @submit.prevent="submit" class="custom-scrollbar max-h-[70vh] overflow-y-auto p-8">
                <!-- Validation Summary -->
                <div
                    v-if="hasErrors"
                    class="mb-6 animate-in fade-in slide-in-from-top-4 flex items-center gap-3 rounded-2xl border border-red-100 bg-red-50 p-4 dark:border-red-900/20 dark:bg-red-900/10"
                >
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-red-500 text-white shadow-lg shadow-red-500/20">
                        <AlertCircle :size="18" />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-red-900 dark:text-red-200">{{ __('Validation Failed') }}</p>
                        <p class="text-xs text-red-700/70 dark:text-red-400/70">
                            {{ __('Please check all tabs for configuration errors.') }}
                        </p>
                    </div>
                </div>
                <!-- Tab: Basic Info -->
                <div v-show="activeTab === 'basic'" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Plan Name') }}</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium transition-all focus:border-primary-500 focus:bg-white focus:ring-4 focus:ring-primary-500/10 dark:border-slate-800 dark:bg-slate-800/50 dark:text-white dark:focus:border-primary-500 dark:focus:bg-slate-800"
                                placeholder="e.g. Diamond Elite"
                            />
                            <p v-if="form.errors.name" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Standard Price') }}</label>
                            <div class="relative">
                                <span class="absolute top-1/2 left-4 -translate-y-1/2 font-bold text-slate-400">{{ currencyPrefix }}</span>
                                <input
                                    v-model="form.price"
                                    type="number"
                                    step="0.01"
                                    required
                                    class="w-full rounded-2xl border-slate-200 bg-slate-50 py-3 pr-4 pl-14 text-sm font-medium transition-all focus:border-primary-500 focus:bg-white focus:ring-4 focus:ring-primary-500/10 dark:border-slate-800 dark:bg-slate-800/50 dark:text-white dark:focus:border-primary-500 dark:focus:bg-slate-800"
                                    placeholder="0.00"
                                />
                            </div>
                            <p v-if="form.errors.price" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.price }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Duration (Days)') }}</label>
                            <input
                                v-model="form.duration_days"
                                type="number"
                                required
                                class="w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium transition-all focus:border-primary-500 focus:bg-white focus:ring-4 focus:ring-primary-500/10 dark:border-slate-800 dark:bg-slate-800/50 dark:text-white dark:focus:border-primary-500 dark:focus:bg-slate-800"
                                placeholder="30"
                            />
                            <p v-if="form.errors.duration_days" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.duration_days }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <div class="flex items-center justify-between">
                                <label class="mb-2 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Access Type') }}</label>
                                <Link
                                    :href="admin.plans.accessTypes.index.url()"
                                    class="mb-2 flex items-center gap-1.5 text-[10px] font-black tracking-widest text-primary-600 uppercase transition-colors hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                                >
                                    <PlusCircle :size="12" />
                                    {{ __('Create New') }}
                                </Link>
                            </div>
                            <div ref="accessTypeDropdownRootRef" class="relative">
                                <button
                                    type="button"
                                    :disabled="selectableAccessTypes.length === 0"
                                    class="inline-flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm font-semibold text-slate-700 transition-all focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-800 dark:bg-slate-800/50 dark:text-white dark:focus:border-primary-500"
                                    @click="accessTypeDropdownOpen ? closeAccessTypeDropdown() : openAccessTypeDropdown()"
                                >
                                    <span class="truncate">
                                        {{ selectedAccessType?.name || __('Select access type') }}
                                    </span>
                                    <ChevronDown
                                        :size="16"
                                        class="shrink-0 text-slate-400 transition-transform"
                                        :class="accessTypeDropdownOpen ? 'rotate-180' : ''"
                                    />
                                </button>

                                <Transition
                                    enter-active-class="transition duration-150 ease-out"
                                    enter-from-class="translate-y-1 opacity-0"
                                    enter-to-class="translate-y-0 opacity-100"
                                    leave-active-class="transition duration-100 ease-in"
                                    leave-from-class="translate-y-0 opacity-100"
                                    leave-to-class="translate-y-1 opacity-0"
                                >
                                    <div
                                        v-if="accessTypeDropdownOpen"
                                        class="absolute z-40 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
                                    >
                                        <div class="border-b border-slate-100 p-2.5 dark:border-slate-700">
                                            <div class="relative">
                                                <Search
                                                    :size="14"
                                                    class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400"
                                                />
                                                <input
                                                    ref="accessTypeSearchInputRef"
                                                    v-model="accessTypeSearchTerm"
                                                    type="text"
                                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pr-3 pl-9 text-sm text-slate-700 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/10 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-primary-500"
                                                    :placeholder="__('Search access type...')"
                                                />
                                            </div>
                                        </div>

                                        <div class="max-h-56 overflow-y-auto p-1.5">
                                            <button
                                                v-for="type in filteredSelectableAccessTypes"
                                                :key="type.slug"
                                                type="button"
                                                class="w-full rounded-xl border px-3 py-2 text-left transition-colors"
                                                :class="
                                                    type.slug === form.access_type
                                                        ? 'border-primary-200 bg-primary-50 dark:border-primary-500/40 dark:bg-primary-500/10'
                                                        : 'border-transparent hover:bg-primary-50 dark:hover:bg-primary-500/10'
                                                "
                                                @click="selectAccessType(type.slug)"
                                            >
                                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ type.name }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                                    {{ type.description || __('No description provided.') }}
                                                </p>
                                                <p
                                                    v-if="type.slug === form.access_type"
                                                    class="mt-1 text-[10px] font-bold tracking-wider text-primary-600 uppercase dark:text-primary-300"
                                                >
                                                    {{ __('Selected') }}
                                                </p>
                                            </button>

                                            <p
                                                v-if="filteredSelectableAccessTypes.length === 0"
                                                class="px-3 py-2 text-xs font-semibold text-slate-500 dark:text-slate-400"
                                            >
                                                {{ __('No matching access type found.') }}
                                            </p>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                            <p v-if="form.errors.access_type" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.access_type }}</p>
                            <p v-if="selectableAccessTypes.length === 0" class="mt-2 text-xs font-bold text-amber-500">
                                {{ __('No active access types available. Please activate or create one first.') }}
                            </p>
                        </div>

                        <div
                            v-if="selectedAccessType"
                            class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 sm:col-span-2 dark:border-slate-800 dark:bg-slate-800/40"
                        >
                            <p class="text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Selected Access Details') }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ selectedAccessType.name }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ selectedAccessType.description || __('No description provided.') }}
                            </p>
                            <p class="mt-2 text-xs font-semibold text-slate-600 dark:text-slate-300">
                                {{
                                    selectedAccessType.is_anytime
                                        ? __('24 Hours Access')
                                        : `${formatAccessTime(selectedAccessType.start_time)} - ${formatAccessTime(selectedAccessType.end_time)}`
                                }}
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Plan Description') }}</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium transition-all focus:border-primary-500 focus:bg-white focus:ring-4 focus:ring-primary-500/10 dark:border-slate-800 dark:bg-slate-800/50 dark:text-white dark:focus:border-primary-500 dark:focus:bg-slate-800"
                                placeholder="Highlight the key benefits..."
                            ></textarea>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Facilities') }}</label>
                            <FacilitiesInput v-model="form.facilities" :available-facilities="facilities" />
                            <p v-if="facilityError" class="mt-2 text-xs font-bold text-red-500">{{ facilityError }}</p>
                        </div>

                        <div class="flex items-center gap-6 sm:col-span-2">
                            <label class="flex cursor-pointer items-center gap-3">
                                <div class="relative">
                                    <input type="checkbox" v-model="form.is_active" class="peer sr-only" />
                                    <div
                                        class="h-6 w-11 rounded-full bg-slate-200 transition-colors peer-checked:bg-emerald-500 dark:bg-slate-800"
                                    ></div>
                                    <div
                                        class="absolute top-1 left-1 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-5"
                                    ></div>
                                </div>
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('Active Status') }}</span>
                            </label>

                            <label class="flex cursor-pointer items-center gap-3">
                                <div class="relative">
                                    <input type="checkbox" v-model="form.is_default" class="peer sr-only" />
                                    <div
                                        class="h-6 w-11 rounded-full bg-slate-200 transition-colors peer-checked:bg-primary-500 dark:bg-slate-800"
                                    ></div>
                                    <div
                                        class="absolute top-1 left-1 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-5"
                                    ></div>
                                </div>
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('Default Plan') }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Tab: Promotion -->
                <div v-show="activeTab === 'promotion'" class="space-y-8">
                    <div class="rounded-3xl border border-primary-100 bg-primary-50/30 p-6 dark:border-primary-900/20 dark:bg-primary-900/10">
                        <div class="flex items-center gap-4">
                            <div class="rounded-2xl bg-primary-500 p-3 text-white shadow-lg shadow-primary-500/30">
                                <Tag :size="24" />
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white">{{ __('Flash Sale Activation') }}</h4>
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    {{ __('Create urgency with time-limited promotional pricing.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/50">
                            <div>
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Enable Promotional Offer') }}</span>
                                <p class="text-xs text-slate-500">{{ __('This will override the standard price until the expiry date.') }}</p>
                            </div>
                            <label class="relative cursor-pointer">
                                <input type="checkbox" v-model="form.is_offer" class="peer sr-only" />
                                <div class="h-6 w-11 rounded-full bg-slate-200 transition-colors peer-checked:bg-primary-500 dark:bg-slate-800"></div>
                                <div
                                    class="absolute top-1 left-1 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-5"
                                ></div>
                            </label>
                        </div>

                        <div v-if="form.is_offer" class="animate-in fade-in slide-in-from-top-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Offer Price') }}</label>
                                <div class="relative">
                                    <span class="absolute top-1/2 left-4 -translate-y-1/2 font-bold text-slate-400">{{ currencyPrefix }}</span>
                                    <input
                                        v-model="form.offer_price"
                                        type="number"
                                        step="0.01"
                                        class="w-full rounded-2xl border-slate-200 bg-white py-3 pr-4 pl-14 text-sm font-medium transition-all focus:border-primary-500 dark:border-slate-800 dark:bg-slate-800 dark:text-white"
                                        placeholder="0.00"
                                    />
                                </div>
                                <p v-if="form.errors.offer_price" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.offer_price }}</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Sale Ends At') }}</label>
                                <input
                                    v-model="form.offer_ends_at"
                                    type="datetime-local"
                                    class="w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm font-medium transition-all focus:border-primary-500 dark:border-slate-800 dark:bg-slate-800 dark:text-white"
                                />
                                <p v-if="form.errors.offer_ends_at" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.offer_ends_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-8 flex justify-end gap-3 border-t border-slate-100 pt-6 dark:border-slate-800">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="rounded-2xl bg-slate-50 px-6 py-3 text-sm font-bold text-slate-600 transition-colors hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex items-center justify-center rounded-2xl bg-primary-600 px-8 py-3 text-sm font-bold text-white shadow-xl shadow-primary-500/20 transition-all hover:bg-primary-700 active:scale-95 disabled:opacity-50"
                    >
                        {{ form.processing ? __('Processing...') : plan ? __('Save Changes') : __('Create Plan') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: var(--color-slate-200);
    border-radius: 9999px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: var(--color-slate-800);
}
</style>
