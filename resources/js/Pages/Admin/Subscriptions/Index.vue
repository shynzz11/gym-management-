<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { debounce } from 'lodash';
import { CheckCircle2, ChevronDown, Crown, PlusCircle, Repeat, Search, User, UsersRound, X } from 'lucide-vue-next';
import type { PropType} from 'vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppTooltip from '@/Components/Common/AppTooltip.vue';
import ConfirmationModal from '@/Components/Common/ConfirmationModal.vue';
import useCurrency from '@/Composables/useCurrency';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import admin from '@/routes/admin';

const props = defineProps({
    subscriptions: Object as PropType<any>,
    activeSubscriptionAssignments: Array as PropType<Array<{ user_id: number; plan_id: number }>>,
    users: Array as PropType<any[]>,
    plans: Array as PropType<any[]>,
    filters: Object as PropType<{ search?: string }>,
    preselectedCustomerId: Number as PropType<number | null>,
    openAssignModal: {
        type: Boolean,
        default: false,
    },
});
const { formatCurrency } = useCurrency();

const showFormModal = ref(false);
const isSubmitting = ref(false);
const processingActionSubscriptionId = ref<number | null>(null);
const showActionConfirmModal = ref(false);
const pendingActionType = ref<'approve' | 'renew' | 'deny' | null>(null);
const actionTargetSubscription = ref<any>(null);
const showCustomerDropdown = ref(false);
const showPlanDropdown = ref(false);
const customerSearchQuery = ref('');
const planSearchQuery = ref('');
const customerDropdownRef = ref<HTMLElement | null>(null);
const planDropdownRef = ref<HTMLElement | null>(null);
const customerSearchInputRef = ref<HTMLInputElement | null>(null);
const planSearchInputRef = ref<HTMLInputElement | null>(null);
const search = ref(props.filters?.search ?? '');
const subscriptionList = computed(() => props.subscriptions?.data ?? []);
const hasSubscriptions = computed(() => subscriptionList.value.length > 0);
const paginationLinks = computed(() => props.subscriptions?.links ?? []);

watch(
    search,
    debounce((value: string) => {
        router.get(admin.subscriptions.index.url(), { search: value || undefined, page: undefined }, { preserveState: true, preserveScroll: true, replace: true });
    }, 300),
);

const form = useForm({
    user_id: '',
    plan_id: '',
    status: 'active',
    start_date: dayjs().format('YYYY-MM-DD'),
});
const showStartDatePicker = ref(false);
const isStartDateInFuture = computed(() => form.start_date > dayjs().format('YYYY-MM-DD'));

const openCreateModal = () => {
    form.reset();
    form.status = 'active';
    form.start_date = dayjs().format('YYYY-MM-DD');
    form.clearErrors();
    showStartDatePicker.value = false;
    showCustomerDropdown.value = false;
    showPlanDropdown.value = false;
    customerSearchQuery.value = '';
    planSearchQuery.value = '';
    showFormModal.value = true;
};

const closeCreateModal = () => {
    showCustomerDropdown.value = false;
    showPlanDropdown.value = false;
    showFormModal.value = false;
    showStartDatePicker.value = false;
    form.clearErrors();
};

const selectedMember = computed(() => {
    return props.users?.find((user) => `${user.id}` === `${form.user_id}`);
});

const selectedPlan = computed(() => {
    return props.plans?.find((plan) => `${plan.id}` === `${form.plan_id}`);
});

const isOfferActive = (plan: any): boolean => {
    if (!plan?.is_offer || !plan?.offer_ends_at) {
        return false;
    }

    return new Date(plan.offer_ends_at) > new Date();
};

const selectedCustomerLabel = computed(() => {
    if (!selectedMember.value) {
        return 'Select a customer';
    }

    return `${selectedMember.value.name} (${selectedMember.value.email})`;
});

const selectedPlanLabel = computed(() => {
    if (!selectedPlan.value) {
        return 'Select a plan';
    }

    const priceLabel = isOfferActive(selectedPlan.value)
        ? `${formatCurrency(selectedPlan.value.offer_price)} (Offer)`
        : formatCurrency(selectedPlan.value.price);

    return `${selectedPlan.value.name} • ${priceLabel}`;
});

const filteredUsers = computed(() => {
    const query = customerSearchQuery.value.trim().toLowerCase();
    const users = props.users ?? [];

    if (!query) {
        return users;
    }

    return users.filter((user) => {
        return `${user.name} ${user.email}`.toLowerCase().includes(query);
    });
});

const filteredPlans = computed(() => {
    const query = planSearchQuery.value.trim().toLowerCase();
    const plans = props.plans ?? [];

    if (!query) {
        return plans;
    }

    return plans.filter((plan) => {
        return `${plan.name} ${plan.duration_days} ${formatCurrency(plan.price)}`.toLowerCase().includes(query);
    });
});

const openCustomerDropdown = () => {
    showCustomerDropdown.value = true;
    showPlanDropdown.value = false;

    window.setTimeout(() => {
        customerSearchInputRef.value?.focus();
    }, 0);
};

const openPlanDropdown = () => {
    showPlanDropdown.value = true;
    showCustomerDropdown.value = false;

    window.setTimeout(() => {
        planSearchInputRef.value?.focus();
    }, 0);
};

const selectCustomer = (user: any) => {
    form.user_id = `${user.id}`;
    showCustomerDropdown.value = false;
    customerSearchQuery.value = '';
    form.clearErrors('user_id');
};

const selectPlan = (plan: any) => {
    form.plan_id = `${plan.id}`;
    showPlanDropdown.value = false;
    planSearchQuery.value = '';
    form.clearErrors('plan_id');
};

const handleOutsideClick = (event: MouseEvent) => {
    const target = event.target as Node | null;

    if (!target) {
        return;
    }

    if (showCustomerDropdown.value && customerDropdownRef.value && !customerDropdownRef.value.contains(target)) {
        showCustomerDropdown.value = false;
    }

    if (showPlanDropdown.value && planDropdownRef.value && !planDropdownRef.value.contains(target)) {
        showPlanDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);

    if (!props.openAssignModal || !props.preselectedCustomerId) {
        return;
    }

    const selectedUser = props.users?.find((user) => Number(user.id) === Number(props.preselectedCustomerId));

    if (!selectedUser) {
        return;
    }

    openCreateModal();
    selectCustomer(selectedUser);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleOutsideClick);
});

const isRenewingExistingPlan = computed(() => {
    if (!form.user_id || !form.plan_id || form.status !== 'active') {
        return false;
    }

    return (props.activeSubscriptionAssignments ?? []).some((subscription) => {
        return `${subscription.user_id}` === `${form.user_id}` && `${subscription.plan_id}` === `${form.plan_id}`;
    });
});

const submitForm = () => {
    if (!form.user_id || !form.plan_id) {
        return;
    }

    isSubmitting.value = true;

    form.transform((data) => ({
        ...data,
        user_id: Number(data.user_id),
        plan_id: Number(data.plan_id),
    })).post(admin.subscriptions.store.url(), {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

const approveSubscription = (subscriptionId: number) => {
    processingActionSubscriptionId.value = subscriptionId;

    router.patch(
        admin.subscriptions.status.url(subscriptionId),
        { status: 'active' },
        {
            preserveScroll: true,
            onFinish: () => {
                processingActionSubscriptionId.value = null;
            },
        },
    );
};

const renewSubscription = (subscription: any) => {
    processingActionSubscriptionId.value = Number(subscription.id);

    router.post(
        admin.subscriptions.store.url(),
        {
            user_id: Number(subscription.user_id),
            plan_id: Number(subscription.plan_id),
            status: 'active',
            start_date: dayjs().format('YYYY-MM-DD'),
        },
        {
            preserveScroll: true,
            onFinish: () => {
                processingActionSubscriptionId.value = null;
            },
        },
    );
};

const denySubscription = (subscriptionId: number) => {
    processingActionSubscriptionId.value = subscriptionId;

    router.delete(
        admin.subscriptions.destroy.url(subscriptionId),
        {
            preserveScroll: true,
            onFinish: () => {
                processingActionSubscriptionId.value = null;
            },
        },
    );
};

const openActionConfirm = (action: 'approve' | 'renew' | 'deny', subscription: any) => {
    if (!subscription?.id) {
        return;
    }

    pendingActionType.value = action;
    actionTargetSubscription.value = subscription;
    showActionConfirmModal.value = true;
};

const closeActionConfirmModal = () => {
    if (processingActionSubscriptionId.value !== null) {
        return;
    }

    showActionConfirmModal.value = false;
    pendingActionType.value = null;
    actionTargetSubscription.value = null;
};

const actionConfirmConfig = computed(() => {
    if (pendingActionType.value === 'approve') {
        return {
            title: 'Approve this subscription?',
            message: 'The membership will be activated immediately and the customer can access this plan now.',
            confirmText: 'Yes, Approve',
            type: 'warning' as const,
        };
    }

    if (pendingActionType.value === 'deny') {
        return {
            title: 'Deny this subscription?',
            message: 'This will reject the pending subscription request completely. It cannot be undone.',
            confirmText: 'Yes, Deny',
            type: 'danger' as const,
        };
    }

    return {
        title: 'Renew this subscription?',
        message: 'This will extend the current plan end date based on the plan duration.',
        confirmText: 'Yes, Renew',
        type: 'info' as const,
    };
});

const actionConfirmProcessing = computed(() => {
    if (!actionTargetSubscription.value) {
        return false;
    }

    return processingActionSubscriptionId.value === Number(actionTargetSubscription.value.id);
});

const executeConfirmedAction = () => {
    if (!actionTargetSubscription.value || !pendingActionType.value) {
        return;
    }

    const selectedSubscription = actionTargetSubscription.value;
    const selectedAction = pendingActionType.value;

    showActionConfirmModal.value = false;
    pendingActionType.value = null;
    actionTargetSubscription.value = null;

    if (selectedAction === 'approve') {
        approveSubscription(Number(selectedSubscription.id));

        return;
    }

    if (selectedAction === 'deny') {
        denySubscription(Number(selectedSubscription.id));

        return;
    }

    renewSubscription(selectedSubscription);
};

const formatDate = (date: string) => {
    return date ? dayjs(date).format('MMM D, YYYY') : '-';
};

const hasPendingPaymentRequest = (subscription: any): boolean => {
    return Boolean(subscription?.has_pending_payment_request);
};

const renewActionTooltip = (subscription: any): string => {
    if (!hasPendingPaymentRequest(subscription)) {
        return '';
    }

    return 'Renew disabled because payment is pending. Approve or deny pending payment first.';
};
</script>

<template>
    <Head :title="__('Manage Subscriptions')" />

    <AdminLayout>
        <div class="animate-fade-in space-y-8">
            <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                <div>
                    <h2 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ __('Customer Subscriptions') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Monitor digital access passes, renewals, and granted lifetime accounts.') }}
                    </p>
                </div>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="flex items-center rounded-2xl bg-primary-600 px-4 py-2 text-sm font-bold text-white shadow-xl shadow-primary-500/20 transition-all hover:bg-primary-700 active:scale-95"
                >
                    <PlusCircle :size="18" class="me-2" /> {{ __('Grant Access') }}
                </button>
            </div>

            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-800">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 p-6 dark:border-slate-700/50">
                    <h3 class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white">
                        <Repeat class="text-primary-500" :size="20" /> Active Roster
                    </h3>
                    <div class="relative w-full max-w-sm">
                        <Search class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400" :size="16" />
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="__('Search member, email, plan, or status...')"
                            class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pr-3 pl-9 text-sm text-slate-700 placeholder:text-slate-400 focus:ring-2 focus:ring-primary-500/20 focus:outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:placeholder:text-slate-500"
                        />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50">
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Member') }}</th>
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Plan') }}</th>
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Status') }}</th>
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Start Date') }}</th>
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('End Date') }}</th>
                                <th class="p-4 text-end text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr
                                v-for="sub in subscriptionList"
                                :key="sub.id"
                                :class="[
                                    'transition-colors',
                                    sub.status === 'expired'
                                        ? 'bg-red-50/50 hover:bg-red-50 dark:bg-red-900/10 dark:hover:bg-red-900/20'
                                        : 'hover:bg-slate-50/50 dark:hover:bg-slate-800/50',
                                ]"
                            >
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-700"
                                        >
                                            <User :size="16" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ sub.user?.name || 'Unknown' }}</p>
                                            <p class="text-xs text-slate-500">{{ sub.user?.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span
                                        class="inline-flex items-center rounded-md bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-700 dark:bg-brand-500/10 dark:text-brand-400"
                                    >
                                        {{ sub.plan?.name || 'Manual Grant' }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span
                                        v-if="sub.status === 'active'"
                                        class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/20 dark:text-emerald-400"
                                    >
                                        <span class="mr-1.5 h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span> {{ __('Active') }}
                                    </span>
                                    <span
                                        v-else-if="sub.status === 'pending'"
                                        class="inline-flex items-center rounded-full border border-amber-200 bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/20 dark:text-amber-400"
                                    >
                                        {{ __('Pending') }}
                                    </span>
                                    <span
                                        v-else-if="sub.status === 'expired'"
                                        class="inline-flex items-center rounded-full border border-red-200 bg-red-100 px-2.5 py-0.5 text-xs font-bold text-red-800 dark:border-red-500/30 dark:bg-red-500/20 dark:text-red-400"
                                    >
                                        {{ __('Expired') }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-800 dark:bg-slate-700 dark:text-slate-300"
                                    >
                                        {{ sub.status }}
                                    </span>
                                </td>
                                <td class="p-4 text-sm text-slate-600 dark:text-slate-300">
                                    {{ formatDate(sub.start_date) }}
                                </td>
                                <td class="p-4 text-sm text-slate-600 dark:text-slate-300">
                                    {{ formatDate(sub.end_date) }}
                                </td>
                                <td class="p-4 text-end">
                                    <div v-if="sub.status === 'pending'" class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="openActionConfirm('approve', sub)"
                                            class="inline-flex h-9 items-center rounded-lg bg-emerald-600 px-3 text-xs font-bold text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="processingActionSubscriptionId === sub.id"
                                        >
                                            {{ processingActionSubscriptionId === sub.id && pendingActionType === 'approve' ? __('Approving...') : __('Approve') }}
                                        </button>
                                        <button
                                            type="button"
                                            @click="openActionConfirm('deny', sub)"
                                            class="inline-flex h-9 items-center rounded-lg bg-red-600 px-3 text-xs font-bold text-white transition-colors hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="processingActionSubscriptionId === sub.id"
                                        >
                                            {{ processingActionSubscriptionId === sub.id && pendingActionType === 'deny' ? __('Denying...') : __('Deny') }}
                                        </button>
                                    </div>
                                    <AppTooltip v-else-if="['active', 'expired'].includes(sub.status)" :text="renewActionTooltip(sub)">
                                        <span class="inline-flex">
                                            <button
                                                type="button"
                                                @click="openActionConfirm('renew', sub)"
                                                class="inline-flex h-9 items-center rounded-lg bg-primary-600 px-3 text-xs font-bold text-white transition-colors hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60"
                                                :disabled="processingActionSubscriptionId === sub.id || hasPendingPaymentRequest(sub)"
                                            >
                                                {{
                                                    processingActionSubscriptionId === sub.id && pendingActionType === 'renew'
                                                        ? __('Renewing...')
                                                        : hasPendingPaymentRequest(sub)
                                                          ? __('Payment Pending')
                                                          : __('Renew')
                                                }}
                                            </button>
                                        </span>
                                    </AppTooltip>
                                </td>
                            </tr>
                            <tr v-if="!hasSubscriptions">
                                <td colspan="6" class="p-8 text-center text-sm text-slate-500 italic">{{ __('No subscriptions found.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="hasSubscriptions && paginationLinks.length > 3"
                    class="flex items-center justify-between border-t border-slate-100 px-6 py-4 dark:border-slate-700/50"
                >
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">
                        {{ __('Page') }} {{ subscriptions.current_page }} / {{ subscriptions.last_page }}
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, index) in paginationLinks"
                            :key="index"
                            :href="link.url || '#'"
                            class="rounded-lg px-3 py-1 text-xs font-semibold transition-colors"
                            :class="
                                link.active
                                    ? 'bg-primary-600 text-white'
                                    : 'text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700'
                            "
                            :preserve-state="true"
                            :preserve-scroll="true"
                        >
                            <span v-html="link.label"></span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-3 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-2 opacity-0"
            >
                <div v-if="showFormModal" class="fixed inset-0 z-[160] flex items-center justify-center p-4 sm:p-6">
                    <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" @click="closeCreateModal"></div>

                    <div
                        class="relative w-full max-w-2xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
                    >
                        <div
                            class="flex items-start justify-between border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-6 py-5 dark:border-slate-700 dark:from-slate-900 dark:to-slate-900"
                        >
                            <div>
                                <p class="text-xs font-black tracking-widest text-primary-600 uppercase dark:text-primary-300">
                                    {{ __('Subscriptions') }}
                                </p>
                                <h3 class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ __('Grant Member Access') }}</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    {{ __('Assign a plan to a customer and set the initial activation status.') }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                                @click="closeCreateModal"
                            >
                                <X :size="18" />
                            </button>
                        </div>

                        <form @submit.prevent="submitForm" class="space-y-6 p-6">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{
                                        __('Customer')
                                    }}</label>
                                    <div ref="customerDropdownRef" class="relative">
                                        <button
                                            type="button"
                                            class="inline-flex h-11 w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-3 text-left text-sm text-slate-800 transition-colors focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                            @click="showCustomerDropdown ? (showCustomerDropdown = false) : openCustomerDropdown()"
                                        >
                                            <span class="truncate">{{ selectedCustomerLabel }}</span>
                                            <ChevronDown
                                                :size="16"
                                                class="ms-2 shrink-0 text-slate-500"
                                                :class="showCustomerDropdown ? 'rotate-180' : ''"
                                            />
                                        </button>

                                        <div
                                            v-if="showCustomerDropdown"
                                            class="absolute top-full right-0 left-0 z-30 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
                                        >
                                            <div class="border-b border-slate-200 p-2 dark:border-slate-700">
                                                <div class="relative">
                                                    <Search
                                                        :size="16"
                                                        class="pointer-events-none absolute top-1/2 left-2.5 -translate-y-1/2 text-slate-400"
                                                    />
                                                    <input
                                                        ref="customerSearchInputRef"
                                                        v-model="customerSearchQuery"
                                                        type="text"
                                                        :placeholder="__('Search customer by name or email')"
                                                        class="h-10 w-full rounded-lg border border-slate-300 bg-white pr-3 pl-8 text-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                                        @keydown.esc.prevent="showCustomerDropdown = false"
                                                    />
                                                </div>
                                            </div>

                                            <div class="max-h-56 overflow-y-auto p-1">
                                                <button
                                                    v-for="user in filteredUsers"
                                                    :key="user.id"
                                                    type="button"
                                                    class="w-full rounded-lg px-2.5 py-2 text-left transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                                                    :class="`${form.user_id}` === `${user.id}` ? 'bg-primary-50 dark:bg-primary-500/10' : ''"
                                                    @click="selectCustomer(user)"
                                                >
                                                    <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ user.name }}</p>
                                                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ user.email }}</p>
                                                </button>
                                                <p v-if="!filteredUsers.length" class="px-2 py-4 text-center text-xs font-medium text-slate-500">
                                                    {{ __('No customer found.') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.user_id" class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                                        {{ form.errors.user_id }}
                                    </p>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Plan') }}</label>
                                    <div ref="planDropdownRef" class="relative">
                                        <button
                                            type="button"
                                            class="inline-flex h-11 w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-3 text-left text-sm text-slate-800 transition-colors focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                            @click="showPlanDropdown ? (showPlanDropdown = false) : openPlanDropdown()"
                                        >
                                            <span class="truncate">{{ selectedPlanLabel }}</span>
                                            <ChevronDown
                                                :size="16"
                                                class="ms-2 shrink-0 text-slate-500"
                                                :class="showPlanDropdown ? 'rotate-180' : ''"
                                            />
                                        </button>

                                        <div
                                            v-if="showPlanDropdown"
                                            class="absolute top-full right-0 left-0 z-30 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
                                        >
                                            <div class="border-b border-slate-200 p-2 dark:border-slate-700">
                                                <div class="relative">
                                                    <Search
                                                        :size="16"
                                                        class="pointer-events-none absolute top-1/2 left-2.5 -translate-y-1/2 text-slate-400"
                                                    />
                                                    <input
                                                        ref="planSearchInputRef"
                                                        v-model="planSearchQuery"
                                                        type="text"
                                                        :placeholder="__('Search plan by name, price, or duration')"
                                                        class="h-10 w-full rounded-lg border border-slate-300 bg-white pr-3 pl-8 text-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                                        @keydown.esc.prevent="showPlanDropdown = false"
                                                    />
                                                </div>
                                            </div>

                                            <div class="max-h-56 overflow-y-auto p-1">
                                                <button
                                                    v-for="plan in filteredPlans"
                                                    :key="plan.id"
                                                    type="button"
                                                    class="w-full rounded-lg px-2.5 py-2 text-left transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                                                    :class="`${form.plan_id}` === `${plan.id}` ? 'bg-primary-50 dark:bg-primary-500/10' : ''"
                                                    @click="selectPlan(plan)"
                                                >
                                                    <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ plan.name }}</p>
                                                    <div class="flex items-center gap-2 text-xs">
                                                        <span v-if="isOfferActive(plan)" class="font-bold text-orange-600 dark:text-orange-400">
                                                            {{ formatCurrency(plan.offer_price) }}
                                                        </span>
                                                        <span :class="{ 'line-through opacity-50': isOfferActive(plan) }" class="text-slate-500 dark:text-slate-400">
                                                            {{ formatCurrency(plan.price) }}
                                                        </span>
                                                        <span class="text-slate-400">•</span>
                                                        <span class="text-slate-500 dark:text-slate-400">{{ plan.duration_days }} {{ __('Days') }}</span>
                                                    </div>
                                                </button>
                                                <p v-if="!filteredPlans.length" class="px-2 py-4 text-center text-xs font-medium text-slate-500">
                                                    {{ __('No plan found.') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.plan_id" class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                                        {{ form.errors.plan_id }}
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                    @click="showStartDatePicker = !showStartDatePicker"
                                >
                                    {{ showStartDatePicker ? __('Hide Start Date') : __('Select Start Date') }}
                                </button>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ __('Starts today by default. Choose another start date if needed.') }}
                                </p>
                                <div v-if="showStartDatePicker">
                                    <label class="mb-1.5 block text-xs font-bold tracking-widest text-slate-500 uppercase">
                                        {{ __('Membership Start Date') }}
                                    </label>
                                    <input
                                        v-model="form.start_date"
                                        type="date"
                                        class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-800 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    />
                                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                                        {{
                                            isStartDateInFuture
                                                ? __('Membership access will remain pending until the selected start date.')
                                                : __('The membership cycle and renewal dates will be based on this date.')
                                        }}
                                    </p>
                                    <p v-if="form.errors.start_date" class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                                        {{ form.errors.start_date }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{
                                    __('Initial Status')
                                }}</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border text-sm font-semibold transition-colors"
                                        :class="
                                            form.status === 'active'
                                                ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'
                                        "
                                        @click="form.status = 'active'"
                                    >
                                        <CheckCircle2 :size="15" /> {{ __('Active') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border text-sm font-semibold transition-colors"
                                        :class="
                                            form.status === 'pending'
                                                ? 'border-amber-500 bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300'
                                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'
                                        "
                                        @click="form.status = 'pending'"
                                    >
                                        <Crown :size="15" /> {{ __('Pending') }}
                                    </button>
                                </div>
                                <p v-if="form.errors.status" class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                                    {{ form.errors.status }}
                                </p>
                            </div>

                            <div
                                class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-2 dark:border-slate-700 dark:bg-slate-800/50"
                            >
                                <div>
                                    <p class="text-[11px] font-black tracking-widest text-slate-500 uppercase">{{ __('Selected Customer') }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">
                                        {{ selectedMember?.name || __('Not selected') }}
                                    </p>
                                    <p class="text-xs text-slate-500">{{ selectedMember?.email || '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-black tracking-widest text-slate-500 uppercase">{{ __('Plan Summary') }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">
                                        {{ selectedPlan?.name || __('Not selected') }}
                                    </p>
                                    <div v-if="selectedPlan" class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <p v-if="isOfferActive(selectedPlan)" class="text-xs font-bold text-orange-600 dark:text-orange-400">
                                                {{ formatCurrency(selectedPlan.offer_price) }}
                                            </p>
                                            <p :class="{ 'line-through opacity-50': isOfferActive(selectedPlan) }" class="text-xs text-slate-500">
                                                {{ formatCurrency(selectedPlan.price) }}
                                            </p>
                                        </div>
                                        <p class="text-xs text-slate-500">{{ selectedPlan.duration_days }} {{ __('Days') }}</p>
                                    </div>
                                    <p v-else class="text-xs text-slate-500">—</p>
                                </div>
                            </div>

                            <div
                                v-if="isRenewingExistingPlan"
                                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300"
                            >
                                {{
                                    __(
                                        'This customer already has this plan. Saving will reset the subscription cycle from the selected start date.',
                                    )
                                }}
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
                                <div class="inline-flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                    <UsersRound :size="14" />
                                    {{
                                        isStartDateInFuture
                                            ? __('Subscription remains pending until the selected start date.')
                                            : __('Subscription starts immediately after confirmation.')
                                    }}
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex h-10 items-center rounded-xl border border-slate-300 bg-white px-4 text-xs font-bold tracking-widest text-slate-700 uppercase transition-colors hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                                        @click="closeCreateModal"
                                    >
                                        {{ __('Cancel') }}
                                    </button>
                                    <button
                                        type="submit"
                                        class="inline-flex h-10 items-center rounded-xl bg-primary-600 px-4 text-xs font-bold tracking-widest text-white uppercase shadow-lg shadow-primary-500/20 transition-colors hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="isSubmitting || form.processing || !form.user_id || !form.plan_id"
                                    >
                                        {{
                                            isSubmitting || form.processing
                                                ? __('Saving...')
                                                : isRenewingExistingPlan
                                                  ? __('Renew Subscription')
                                                  : __('Create Subscription')
                                        }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <ConfirmationModal
            :show="showActionConfirmModal"
            :title="actionConfirmConfig.title"
            :message="actionConfirmConfig.message"
            :confirmText="actionConfirmConfig.confirmText"
            :cancelText="__('Cancel')"
            :type="actionConfirmConfig.type"
            :processing="actionConfirmProcessing"
            @close="closeActionConfirmModal"
            @confirm="executeConfirmedAction"
        />
    </AdminLayout>
</template>
