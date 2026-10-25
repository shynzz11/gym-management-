<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    PlusCircle,
    Dumbbell,
    Trash2,
    Zap,
    Edit3,
    ShieldCheck,
    Power,
    AlertCircle,
    Settings2,
    Wallet,
    CalendarDays,
    Users,
    Clock3,
    Tag,
    X,
} from 'lucide-vue-next';
import type { PropType} from 'vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AppTooltip from '@/Components/Common/AppTooltip.vue';
import ConfirmationModal from '@/Components/Common/ConfirmationModal.vue';
import useCurrency from '@/Composables/useCurrency';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import admin from '@/routes/admin';
import PlanFormModal from './Partials/PlanFormModal.vue';

const props = defineProps({
    plans: Array as PropType<any[]>,
    accessTypes: Array as PropType<any[]>,
    facilities: Array as PropType<any[]>,
});

const page = usePage();
const businessSettings = computed(() => (page.props.branding as any)?.business_settings || {});
const brandName = computed(() => businessSettings.value.business_name || 'Gym Management');
const { formatCurrency } = useCurrency();

// Modals
const showDeleteModal = ref(false);
const showStatusModal = ref(false);
const showFormModal = ref(false);
const planToDelete = ref<any>(null);
const planToToggle = ref<any>(null);
const editingPlan = ref<any>(null);
const selectedPlanId = ref<number | null>(null);

const selectedPlan = computed(() => {
    if (selectedPlanId.value === null) {
        return null;
    }

    return props.plans?.find((plan: any) => plan.id === selectedPlanId.value) ?? null;
});

const selectedPlanFacilities = computed((): string[] => {
    const facilities = selectedPlan.value?.facilities;

    if (!Array.isArray(facilities)) {
        return [];
    }

    const normalizedFacilities = facilities
        .filter((facility: unknown): facility is string => typeof facility === 'string')
        .map((facility) => facility.trim())
        .filter((facility) => facility.length > 0);

    return normalizedFacilities.filter((facility, index) => {
        return normalizedFacilities.findIndex((item) => item.toLowerCase() === facility.toLowerCase()) === index;
    });
});

const selectedPlanAccessType = computed(() => {
    if (!selectedPlan.value) {
        return null;
    }

    return props.accessTypes?.find((accessType: any) => accessType.slug === selectedPlan.value.access_type) ?? null;
});

const selectedPlanAccessWindow = computed((): string => {
    return getPlanAccessWindow(selectedPlan.value);
});

const getPlanAccessWindow = (plan: any): string => {
    if (!plan) {
        return '--';
    }

    const accessType = props.accessTypes?.find((at: any) => at.slug === plan.access_type);

    if (accessType?.is_anytime) {
        return '24 Hours';
    }

    const startTime = accessType?.start_time ?? plan.start_time;
    const endTime = accessType?.end_time ?? plan.end_time;

    if (!startTime || !endTime) {
        return '--';
    }

    return `${formatAccessTime(String(startTime))} - ${formatAccessTime(String(endTime))}`;
};

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

const isOfferActive = (plan: any): boolean => {
    if (!plan.is_offer || !plan.offer_ends_at) {
        return false;
    }

    const endsAt = new Date(plan.offer_ends_at);
    return endsAt > new Date();
};

const formatDateTime = (value?: string | null): string => {
    if (!value) {
        return '--';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString();
};

const openCreateModal = () => {
    editingPlan.value = null;
    showFormModal.value = true;
};

const openEditModal = (plan: any) => {
    editingPlan.value = plan;
    showFormModal.value = true;
};

const openPlanDetails = (plan: any) => {
    selectedPlanId.value = plan.id;
};

const closePlanDetails = () => {
    selectedPlanId.value = null;
};

const handleWindowKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && selectedPlanId.value !== null) {
        closePlanDetails();
    }
};

onMounted(() => window.addEventListener('keydown', handleWindowKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleWindowKeydown));

const confirmToggleActive = (plan: any) => {
    if (plan.is_default && plan.is_active) {
        return;
    }

    planToToggle.value = plan;
    showStatusModal.value = true;
};

const closeStatusModal = () => {
    showStatusModal.value = false;
    planToToggle.value = null;
};

const toggleActive = () => {
    if (!planToToggle.value) {
        return;
    }

    router.patch(
        admin.plans.toggleActive.url(planToToggle.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                closeStatusModal();
            },
        },
    );
};

const confirmDelete = (plan: any) => {
    if (plan.is_default || (plan.subscriptions_count && plan.subscriptions_count > 0)) {
        return;
    }
    planToDelete.value = plan;
    showDeleteModal.value = true;
};

const getDeleteDisabledMessage = (plan: any): string => {
    if (plan.is_default) {
        return 'Default cannot be deleted';
    }

    if (Number(plan.subscriptions_count ?? 0) > 0) {
        return 'Active subscribers protection';
    }

    return '';
};

const handleDelete = () => {
    if (!planToDelete.value) return;

    router.delete(admin.plans.destroy.url(planToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            planToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head :title="__('Manage Memberships')" />

    <AdminLayout>
        <div class="animate-fade-in space-y-8">
            <!-- Header Section -->
            <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ __('Membership Plans') }}</h2>
                    <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">
                        {{ __('Configure your training plans, pricing structures, and community access rules.') }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <Link
                        :href="admin.plans.accessTypes.index.url()"
                        class="group inline-flex h-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-bold whitespace-nowrap text-slate-700 shadow-sm transition-all hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        <Clock3 :size="16" class="me-2 shrink-0 text-primary-600 dark:text-primary-400" />
                        {{ __('Access Types') }}
                    </Link>
                    <Link
                        :href="admin.plans.facilities.index.url()"
                        class="group inline-flex h-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-bold whitespace-nowrap text-slate-700 shadow-sm transition-all hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        <Settings2 :size="16" class="me-2 shrink-0 text-primary-600 dark:text-primary-400" />
                        {{ __('Manage Facilities') }}
                    </Link>
                    <button
                        @click.prevent="openCreateModal"
                        class="group inline-flex h-11 shrink-0 cursor-pointer items-center justify-center rounded-xl bg-primary-600 px-6 text-sm font-bold whitespace-nowrap text-white shadow-xl shadow-primary-500/20 transition-all hover:bg-primary-700 active:scale-95"
                    >
                        <PlusCircle :size="18" class="me-2 shrink-0 transition-transform group-hover:rotate-90" />
                        {{ __('Create New Plan') }}
                    </button>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div
                    class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl border border-primary-100 bg-primary-50 text-primary-600 dark:border-primary-500/20 dark:bg-primary-500/10"
                    >
                        <Dumbbell :size="24" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase">{{ __('Total Plans') }}</p>
                        <p class="text-3xl font-black text-slate-900 dark:text-white">{{ plans?.length || 0 }}</p>
                    </div>
                </div>

                <div
                    class="relative flex items-center justify-between overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-6 shadow-xl md:col-span-2"
                >
                    <div class="absolute -right-10 -bottom-10 opacity-10">
                        <Zap :size="150" class="text-primary-500" />
                    </div>
                    <div class="relative z-10">
                        <p class="mb-1 text-[10px] font-black tracking-widest text-primary-500 uppercase">{{ __('Revenue Ecosystem') }}</p>
                        <h3 class="text-xl font-bold text-white">{{ brandName }} Membership Hub</h3>
                        <p class="mt-2 max-w-sm text-xs leading-relaxed font-medium text-slate-400">
                            Efficiently scale your fitness operation by managing service plans that drive member conversion and retention.
                        </p>
                    </div>
                    <div class="relative z-10 hidden lg:block">
                        <div class="rounded-2xl bg-white/5 p-4 backdrop-blur-md">
                            <ShieldCheck class="mx-auto mb-2 text-primary-500" :size="32" />
                            <p class="text-center text-[10px] font-bold tracking-tighter text-white uppercase">{{ __('Core Protected') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plans Grid -->
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                <!-- Plan Card -->
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    role="button"
                    tabindex="0"
                    @click="openPlanDetails(plan)"
                    @keydown.enter.prevent="openPlanDetails(plan)"
                    @keydown.space.prevent="openPlanDetails(plan)"
                    class="group relative flex cursor-pointer flex-col justify-between overflow-hidden rounded-2xl border bg-white p-8 shadow-sm transition-all duration-500 hover:-translate-y-1 hover:border-primary-500/50 hover:shadow-2xl dark:bg-slate-900"
                    :class="
                        selectedPlanId === plan.id
                            ? 'border-primary-500 ring-2 ring-primary-200 dark:border-primary-500/60 dark:ring-primary-500/20'
                            : 'border-slate-200/60 dark:border-slate-800'
                    "
                >
                    <!-- Background Icon Decoration -->
                    <div
                        class="pointer-events-none absolute -top-6 -right-6 p-4 opacity-5 transition-all duration-700 group-hover:scale-110 group-hover:rotate-12 group-hover:opacity-10 dark:text-white"
                    >
                        <Dumbbell :size="140" />
                    </div>

                    <div class="relative z-10 mb-8 w-full">
                        <div class="mb-6 flex items-start justify-between">
                            <div class="space-y-1">
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ plan.name }}</h3>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        class="rounded-lg bg-slate-100 px-2.5 py-1 text-[10px] font-bold tracking-widest text-slate-600 uppercase dark:bg-slate-800 dark:text-slate-400"
                                    >
                                        {{ plan.duration_days }} {{ __('Days') }}
                                    </span>
                                    <span
                                        v-if="plan.is_default"
                                        class="flex items-center rounded-lg bg-primary-500 px-2.5 py-1 text-[10px] font-bold tracking-widest text-white uppercase shadow-lg shadow-primary-500/20"
                                    >
                                        <ShieldCheck :size="10" class="mr-1" /> {{ __('Default') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Status Toggle Link -->
                            <button
                                @click.stop="confirmToggleActive(plan)"
                                :disabled="plan.is_default && plan.is_active"
                                class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-300 active:scale-90 disabled:opacity-30"
                                :class="
                                    plan.is_active
                                        ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400'
                                        : 'bg-slate-100 text-slate-400 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-500'
                                "
                            >
                                <Power :size="18" />
                            </button>
                        </div>

                        <div class="mb-6 flex flex-col gap-1">
                            <div class="flex items-baseline gap-1" :class="{ 'opacity-50 line-through scale-75 origin-left -mb-1': isOfferActive(plan) }">
                                <span
                                    class="text-4xl font-black text-slate-900 transition-colors group-hover:text-primary-600 dark:text-white dark:group-hover:text-primary-400"
                                    >{{ formatCurrency(plan.price) }}</span
                                >
                                <span class="text-xs font-bold tracking-widest text-slate-400 uppercase">/ {{ plan.duration_days }}d</span>
                            </div>

                            <div v-if="isOfferActive(plan)" class="flex items-baseline gap-1 animate-in fade-in slide-in-from-left-2">
                                <span
                                    class="text-4xl font-black text-orange-600 transition-colors dark:text-orange-500"
                                    >{{ formatCurrency(plan.offer_price) }}</span
                                >
                                <span class="text-xs font-bold tracking-widest text-orange-400 uppercase">/ {{ plan.duration_days }}d</span>
                                <div class="ms-2 rounded-full bg-orange-100 px-2 py-0.5 text-[8px] font-black tracking-widest text-orange-600 uppercase dark:bg-orange-500/10 dark:text-orange-400">
                                    {{ __('Promotion') }}
                                </div>
                            </div>
                        </div>

                        <div class="mb-6 flex items-center gap-2">
                            <Clock3 :size="14" class="text-primary-500" />
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">
                                {{ getPlanAccessWindow(plan) }}
                            </span>
                            <span class="mx-1 text-slate-300 dark:text-slate-700">•</span>
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                {{ props.accessTypes?.find((at: any) => at.slug === plan.access_type)?.name || plan.access_type }}
                            </span>
                        </div>

                        <p class="min-h-[3rem] text-sm leading-relaxed font-medium text-slate-500 dark:text-slate-400">
                            {{ plan.description || __('Premium access to all facility amenities and digital training resources.') }}
                        </p>
                    </div>

                    <!-- Footer Actions -->
                    <div class="relative z-10 mt-auto flex items-center justify-between border-t border-slate-100 pt-6 dark:border-slate-800">
                        <div class="flex items-center gap-4">
                            <AppTooltip :text="getDeleteDisabledMessage(plan) ? __(getDeleteDisabledMessage(plan)) : ''">
                                <button
                                    @click.stop="confirmDelete(plan)"
                                    :disabled="plan.is_default || plan.subscriptions_count > 0"
                                    class="flex h-9 w-9 items-center justify-center rounded-lg transition-colors disabled:opacity-20"
                                    :class="
                                        !(plan.is_default || plan.subscriptions_count > 0)
                                            ? 'text-slate-400 hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-500/10'
                                            : 'cursor-not-allowed text-slate-300 dark:text-slate-600'
                                    "
                                >
                                    <Trash2 :size="18" class="pointer-events-none" />
                                </button>
                            </AppTooltip>

                            <div class="flex items-center gap-1.5 rounded-xl bg-slate-50 px-3 py-1.5 dark:bg-slate-800/50">
                                <div
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="plan.subscriptions_count > 0 ? 'animate-pulse bg-indigo-500' : 'bg-slate-300 dark:bg-slate-600'"
                                ></div>
                                <span class="text-[10px] font-black tracking-tighter text-slate-500 uppercase">
                                    {{ plan.subscriptions_count || 0 }} {{ __('Subscribers') }}
                                </span>
                            </div>
                        </div>

                        <button
                            @click.stop="openEditModal(plan)"
                            class="flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm transition-all hover:bg-slate-50 active:scale-95 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            <Edit3 :size="16" /> {{ __('Edit') }}
                        </button>
                    </div>
                </div>

                <!-- Create New Card -->
                <button
                    @click.prevent="openCreateModal"
                    class="group relative flex min-h-[350px] cursor-pointer flex-col items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-slate-200 p-8 transition-all hover:border-primary-500 hover:bg-primary-50/10 dark:border-slate-800 dark:hover:border-primary-500/50 dark:hover:bg-primary-500/5"
                >
                    <div
                        class="flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-100 text-slate-400 transition-all duration-500 group-hover:scale-110 group-hover:bg-primary-600 group-hover:text-white group-hover:shadow-2xl group-hover:shadow-primary-600/30 dark:bg-slate-800"
                    >
                        <PlusCircle :size="32" />
                    </div>
                    <div class="mt-6 text-center">
                        <p
                            class="text-lg font-black text-slate-600 transition-colors group-hover:text-primary-600 dark:text-slate-400 dark:group-hover:text-primary-400"
                        >
                            {{ __('Create New Plan') }}
                        </p>
                        <p class="mx-auto mt-2 max-w-[180px] text-xs leading-relaxed font-medium text-slate-400">
                            Scale your revenue with high-performance membership structures.
                        </p>
                    </div>
                </button>
            </div>

            <!-- Integrity Warning Footer -->
            <div class="flex items-start gap-4 rounded-2xl border border-amber-100 bg-amber-50 p-6 dark:border-amber-500/20 dark:bg-amber-500/5">
                <div class="rounded-xl bg-amber-100 p-2 text-amber-600 dark:bg-amber-500/20 dark:text-amber-500">
                    <AlertCircle :size="20" />
                </div>
                <div>
                    <h4 class="text-sm font-black tracking-tight text-amber-900 uppercase dark:text-amber-200">{{ __('System Integrity Guard') }}</h4>
                    <p class="mt-1 text-xs leading-relaxed font-medium text-amber-700 transition-colors dark:text-amber-400">
                        To maintain financial history integrity, plans with subscribers cannot be deleted. Deactivation is recommended for retired
                        plans. The default plan is protected against deletion or deactivation to ensure a stable registration flow.
                    </p>
                </div>
            </div>
        </div>

        <!-- Create/Edit Form Modal -->
        <PlanFormModal
            :show="showFormModal"
            :plan="editingPlan"
            :access-types="accessTypes"
            :facilities="facilities"
            @close="showFormModal = false"
        />

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="selectedPlan" class="fixed inset-0 z-[140] flex items-center justify-center p-4 sm:p-6">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closePlanDetails"></div>

                <div
                    class="relative z-10 w-full max-w-5xl overflow-hidden rounded-2xl border border-slate-300 bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200 shadow-2xl shadow-slate-900/20 sm:max-h-[90vh] dark:border-slate-700 dark:from-slate-900 dark:via-slate-900 dark:to-slate-800"
                >
                    <div
                        class="sticky top-0 z-20 border-b border-slate-200/80 bg-slate-100/95 px-5 py-4 backdrop-blur sm:px-6 dark:border-slate-700/80 dark:bg-slate-900/95"
                    >
                        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                            <div>
                                <p class="text-[10px] font-black tracking-[0.24em] text-blue-600 uppercase dark:text-blue-400">
                                    {{ __('Selected Plan') }}
                                </p>
                                <h3 class="mt-1.5 text-2xl font-black tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                                    {{ selectedPlan.name }}
                                </h3>
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <span
                                        class="rounded-full border border-slate-300 bg-white px-2.5 py-0.5 text-[10px] font-bold tracking-wide text-slate-700 uppercase dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                                    >
                                        {{ selectedPlan.is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                    <span
                                        v-if="selectedPlan.is_default"
                                        class="rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-[10px] font-bold tracking-wide text-blue-700 uppercase dark:border-blue-400/30 dark:bg-blue-500/10 dark:text-blue-300"
                                    >
                                        {{ __('Default') }}
                                    </span>
                                    <span
                                        class="rounded-full border border-slate-300 bg-white px-2.5 py-0.5 text-[10px] font-bold tracking-wide text-slate-700 uppercase dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                                    >
                                        {{ selectedPlanAccessType?.name || selectedPlan.access_type || __('Access') }}
                                    </span>
                                </div>
                            </div>
                            <button
                                @click="closePlanDetails"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 bg-slate-100 text-slate-700 transition-colors hover:bg-white dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            >
                                <X :size="18" />
                            </button>
                        </div>
                    </div>

                    <div class="max-h-[calc(90vh-9rem)] overflow-y-auto px-5 py-5 sm:px-6 sm:py-6">
                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-4">
                            <div class="rounded-2xl border border-slate-300 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                <div class="flex items-center gap-2 text-slate-500 dark:text-slate-300">
                                    <Wallet :size="14" />
                                    <p class="text-[10px] font-black tracking-[0.18em] uppercase">{{ __('Price') }}</p>
                                </div>
                                <p class="mt-2 text-xl font-black text-slate-900 dark:text-white">{{ formatCurrency(selectedPlan.price) }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-300 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                <div class="flex items-center gap-2 text-slate-500 dark:text-slate-300">
                                    <CalendarDays :size="14" />
                                    <p class="text-[10px] font-black tracking-[0.18em] uppercase">{{ __('Duration') }}</p>
                                </div>
                                <p class="mt-2 text-xl font-black text-slate-900 dark:text-white">
                                    {{ selectedPlan.duration_days }} {{ __('Days') }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-slate-300 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                <div class="flex items-center gap-2 text-slate-500 dark:text-slate-300">
                                    <Users :size="14" />
                                    <p class="text-[10px] font-black tracking-[0.18em] uppercase">{{ __('Subscribers') }}</p>
                                </div>
                                <p class="mt-2 text-xl font-black text-slate-900 dark:text-white">{{ selectedPlan.subscriptions_count || 0 }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-300 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                <div class="flex items-center gap-2 text-slate-500 dark:text-slate-300">
                                    <Clock3 :size="14" />
                                    <p class="text-[10px] font-black tracking-[0.18em] uppercase">{{ __('Access Window') }}</p>
                                </div>
                                <p class="mt-2 text-sm font-black text-slate-900 dark:text-white">{{ selectedPlanAccessWindow }}</p>
                            </div>
                        </div>

                        <div class="mt-3 rounded-2xl border border-slate-300 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                            <p class="text-[10px] font-black tracking-[0.18em] text-slate-500 uppercase dark:text-slate-300">
                                {{ __('Description') }}
                            </p>
                            <p class="mt-2 text-xs leading-relaxed font-semibold text-slate-700 sm:text-sm dark:text-slate-200">
                                {{ selectedPlan.description || __('No description provided for this plan.') }}
                            </p>
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-2">
                            <div class="rounded-2xl border border-slate-300 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                <p class="text-[10px] font-black tracking-[0.18em] text-slate-500 uppercase dark:text-slate-300">
                                    {{ __('Access Type') }}
                                </p>
                                <p class="mt-1.5 text-base font-black text-slate-900 sm:text-lg dark:text-white">
                                    {{ selectedPlanAccessType?.name || selectedPlan.access_type || __('Not set') }}
                                </p>
                                <p class="mt-1 text-[11px] leading-relaxed text-slate-500 sm:text-xs dark:text-slate-400">
                                    {{ selectedPlanAccessType?.description || __('No access type description available.') }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-slate-300 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                <div class="flex items-center gap-2 text-slate-500 dark:text-slate-300">
                                    <Tag :size="14" />
                                    <p class="text-[10px] font-black tracking-[0.18em] uppercase">{{ __('Offer') }}</p>
                                </div>
                                <p class="mt-1.5 text-base font-black text-slate-900 sm:text-lg dark:text-white">
                                    {{ selectedPlan.is_offer ? __('Offer Active') : __('No Active Offer') }}
                                </p>
                                <p v-if="selectedPlan.is_offer" class="mt-1.5 text-xs font-semibold text-slate-600 sm:text-sm dark:text-slate-300">
                                    {{ __('Offer Price:') }} {{ formatCurrency(selectedPlan.offer_price ?? selectedPlan.price) }}
                                </p>
                                <p v-if="selectedPlan.is_offer" class="mt-1 text-[11px] font-medium text-slate-500 sm:text-xs dark:text-slate-400">
                                    {{ __('Offer Ends:') }} {{ formatDateTime(selectedPlan.offer_ends_at) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-5">
                            <div class="flex items-center gap-2">
                                <Settings2 :size="14" class="text-blue-600 dark:text-blue-400" />
                                <p class="text-xs font-black tracking-[0.18em] text-slate-600 uppercase dark:text-slate-300">
                                    {{ __('Facilities Available') }}
                                </p>
                            </div>

                            <ul v-if="selectedPlanFacilities.length > 0" class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                                <li
                                    v-for="facility in selectedPlanFacilities"
                                    :key="facility"
                                    class="rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 sm:text-lg dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                >
                                    {{ facility }}
                                </li>
                            </ul>

                            <p
                                v-else
                                class="mt-3 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400"
                            >
                                {{ __('No facilities are assigned to this plan yet.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Delete Confirmation -->
        <ConfirmationModal
            :show="showDeleteModal"
            :title="__('Delete Membership Plan?')"
            :message="__('Are you sure you want to delete this plan? This action cannot be undone.')"
            :confirmText="__('Yes, Drop Plan')"
            :cancelText="__('No, Keep Plan')"
            type="danger"
            @close="showDeleteModal = false"
            @confirm="handleDelete"
        />

        <ConfirmationModal
            :show="showStatusModal"
            :title="planToToggle?.is_active ? __('Deactivate Membership Plan?') : __('Activate Membership Plan?')"
            :message="
                planToToggle?.is_active
                    ? __('Are you sure you want to deactivate this plan? New subscriptions will not be allowed until reactivated.')
                    : __('Are you sure you want to activate this plan? It will be available for new subscriptions.')
            "
            :confirmText="planToToggle?.is_active ? __('Yes, Deactivate') : __('Yes, Activate')"
            :cancelText="__('Cancel')"
            type="warning"
            @close="closeStatusModal"
            @confirm="toggleActive"
        />
    </AdminLayout>
</template>
