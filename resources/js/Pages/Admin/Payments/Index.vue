<script setup lang="ts">
import ConfirmationModal from '@/Components/Common/ConfirmationModal.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, PropType, ref } from 'vue';
import { ChevronDown, CreditCard, History, Search, User, X } from 'lucide-vue-next';
import admin from '@/routes/admin';
import dayjs from 'dayjs';
import useCurrency from '@/Composables/useCurrency';

const props = defineProps({
    payments: Array as PropType<any[]>,
    customers: Array as PropType<any[]>,
    plans: Array as PropType<any[]>,
    indexUrl: String as PropType<string>,
    filters: Object as PropType<{
        customer_id?: number | null;
        plan_id?: number | null;
        status?: string | null;
    }>,
});

const customerId = ref(props.filters?.customer_id ? `${props.filters.customer_id}` : '');
const planId = ref(props.filters?.plan_id ? `${props.filters.plan_id}` : '');
const status = ref(props.filters?.status ?? '');
const defaultStatusFilter = ref(props.filters?.status ?? '');
const showCustomerDropdown = ref(false);
const showPlanDropdown = ref(false);
const customerSearchQuery = ref('');
const planSearchQuery = ref('');
const customerDropdownRef = ref<HTMLElement | null>(null);
const planDropdownRef = ref<HTMLElement | null>(null);
const customerSearchInputRef = ref<HTMLInputElement | null>(null);
const planSearchInputRef = ref<HTMLInputElement | null>(null);
const searchQuery = ref('');
const showApproveConfirmModal = ref(false);
const showDenyReasonModal = ref(false);
const actionPayment = ref<any | null>(null);
const statusActionProcessingPaymentId = ref<number | null>(null);
const denialReason = ref('');
const showDenyReasonValidation = ref(false);

const formatDate = (date: string) => {
    return date ? dayjs(date).format('MMM D, YYYY h:mm A') : '-';
};

const { formatCurrency } = useCurrency();

const formatTransactionType = (value: string | null | undefined) => {
    if (value === 'renew') {
        return 'Renew';
    }

    if (value === 'new') {
        return 'New';
    }

    return 'N/A';
};

const selectedCustomer = computed(() => {
    return props.customers?.find((customer) => `${customer.id}` === customerId.value);
});

const selectedPlan = computed(() => {
    return props.plans?.find((plan) => `${plan.id}` === planId.value);
});

const selectedCustomerLabel = computed(() => {
    return selectedCustomer.value ? selectedCustomer.value.name : 'All Customers';
});

const selectedPlanLabel = computed(() => {
    return selectedPlan.value ? selectedPlan.value.name : 'All Plans';
});

const filteredCustomers = computed(() => {
    const query = customerSearchQuery.value.trim().toLowerCase();
    const customers = props.customers ?? [];

    if (!query) {
        return customers;
    }

    return customers.filter((customer) => {
        return `${customer.name}`.toLowerCase().includes(query);
    });
});

const filteredPlans = computed(() => {
    const query = planSearchQuery.value.trim().toLowerCase();
    const plans = props.plans ?? [];

    if (!query) {
        return plans;
    }

    return plans.filter((plan) => {
        return `${plan.name}`.toLowerCase().includes(query);
    });
});

const paymentRows = computed(() => props.payments ?? []);
const filteredPaymentRows = computed(() => {
    const normalizedQuery = searchQuery.value.trim().toLowerCase();

    if (!normalizedQuery) {
        return paymentRows.value;
    }

    return paymentRows.value.filter((payment) => {
        const searchableFields = [
            `PAY-${String(payment?.id ?? '').padStart(6, '0')}`,
            String(payment?.subscription?.user?.name ?? ''),
            String(payment?.subscription?.plan?.name ?? ''),
            String(payment?.method ?? ''),
            String(payment?.status ?? ''),
            formatTransactionType(payment?.transaction_type),
        ];

        return searchableFields.some((field) => field.toLowerCase().includes(normalizedQuery));
    });
});
const isPendingOnlyView = computed(() => `${status.value}`.toLowerCase() === 'pending');
const paymentSectionTitle = computed(() => {
    return isPendingOnlyView.value ? 'Pending Payment Requests' : 'Transactions';
});
const hasActiveFilters = computed(() => {
    return customerId.value !== '' || planId.value !== '' || searchQuery.value.trim() !== '';
});
const showActionColumn = computed(() => isPendingOnlyView.value);
const tableColumnCount = computed(() => (showActionColumn.value ? 9 : 8));
const actionModalProcessing = computed(() => {
    return actionPayment.value
        ? statusActionProcessingPaymentId.value === Number(actionPayment.value.id)
        : false;
});
const denyReasonValidationMessage = computed(() => {
    if (!showDenyReasonValidation.value) {
        return '';
    }

    return denialReason.value.trim() === '' ? 'Denial reason is required.' : '';
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

const selectCustomer = (customer: any) => {
    const selectedId = `${customer.id}`;
    const hasChanged = customerId.value !== selectedId;
    customerId.value = selectedId;
    showCustomerDropdown.value = false;
    customerSearchQuery.value = '';

    if (hasChanged) {
        applyFilters();
    }
};

const selectPlan = (plan: any) => {
    const selectedId = `${plan.id}`;
    const hasChanged = planId.value !== selectedId;
    planId.value = selectedId;
    showPlanDropdown.value = false;
    planSearchQuery.value = '';

    if (hasChanged) {
        applyFilters();
    }
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
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleOutsideClick);
});

const applyFilters = () => {
    showCustomerDropdown.value = false;
    showPlanDropdown.value = false;

    router.get(
        props.indexUrl ?? admin.payments.index.url(),
        {
            customer_id: customerId.value || undefined,
            plan_id: planId.value || undefined,
            status: status.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const clearCustomerFilter = (): void => {
    const hasChanged = customerId.value !== '';
    customerId.value = '';
    showCustomerDropdown.value = false;
    customerSearchQuery.value = '';

    if (hasChanged) {
        applyFilters();
    }
};

const clearPlanFilter = (): void => {
    const hasChanged = planId.value !== '';
    planId.value = '';
    showPlanDropdown.value = false;
    planSearchQuery.value = '';

    if (hasChanged) {
        applyFilters();
    }
};

const resetFilters = () => {
    customerId.value = '';
    planId.value = '';
    status.value = defaultStatusFilter.value ?? '';
    searchQuery.value = '';
    customerSearchQuery.value = '';
    planSearchQuery.value = '';
    showCustomerDropdown.value = false;
    showPlanDropdown.value = false;
    applyFilters();
};

const canProcessPayment = (payment: any): boolean => {
    return `${payment?.status ?? ''}`.toLowerCase() === 'pending';
};

const statusPillClass = (statusValue: string | null | undefined): string => {
    const normalizedStatus = `${statusValue ?? ''}`.toLowerCase();

    if (normalizedStatus === 'completed' || normalizedStatus === 'processed' || normalizedStatus === 'approved') {
        return 'border-emerald-200 bg-emerald-100 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/20 dark:text-emerald-400';
    }

    if (normalizedStatus === 'canceled' || normalizedStatus === 'cancelled' || normalizedStatus === 'denied' || normalizedStatus === 'failed') {
        return 'border-red-200 bg-red-100 text-red-700 dark:border-red-500/30 dark:bg-red-500/20 dark:text-red-400';
    }

    return 'border-amber-200 bg-amber-100 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/20 dark:text-amber-400';
};

const paymentStatusTooltip = (payment: any): string => {
    const normalizedStatus = `${payment?.status ?? ''}`.toLowerCase();
    const denialReasonText = String(payment?.denial_reason ?? '').trim();

    if (denialReasonText !== '') {
        if (normalizedStatus === 'canceled' || normalizedStatus === 'cancelled') {
            return `Cancellation reason: ${denialReasonText}`;
        }

        return `Denial reason: ${denialReasonText}`;
    }

    if (normalizedStatus === 'pending') {
        return 'Pending admin action.';
    }

    if (normalizedStatus === 'canceled' || normalizedStatus === 'cancelled') {
        return 'Payment canceled.';
    }

    if (normalizedStatus === 'completed' || normalizedStatus === 'processed') {
        return 'Payment completed.';
    }

    return '';
};

const openApproveConfirm = (payment: any): void => {
    if (!canProcessPayment(payment)) {
        return;
    }

    actionPayment.value = payment;
    showApproveConfirmModal.value = true;
};

const closeApproveConfirm = (): void => {
    if (actionModalProcessing.value) {
        return;
    }

    showApproveConfirmModal.value = false;
    actionPayment.value = null;
};

const openDenyReasonDialog = (payment: any): void => {
    if (!canProcessPayment(payment)) {
        return;
    }

    actionPayment.value = payment;
    denialReason.value = '';
    showDenyReasonValidation.value = false;
    showDenyReasonModal.value = true;
};

const closeDenyReasonDialog = (): void => {
    if (actionModalProcessing.value) {
        return;
    }

    showDenyReasonModal.value = false;
    denialReason.value = '';
    showDenyReasonValidation.value = false;
    actionPayment.value = null;
};

const submitPaymentStatusAction = (decision: 'approve' | 'deny'): void => {
    if (!actionPayment.value?.id) {
        return;
    }

    if (decision === 'deny') {
        showDenyReasonValidation.value = true;

        if (denialReason.value.trim() === '') {
            return;
        }
    }

    const paymentId = Number(actionPayment.value.id);
    statusActionProcessingPaymentId.value = paymentId;

    router.patch(
        admin.payments.status.url(paymentId),
        {
            decision,
            denial_reason: decision === 'deny' ? denialReason.value.trim() : undefined,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showApproveConfirmModal.value = false;
                showDenyReasonModal.value = false;
                actionPayment.value = null;
                denialReason.value = '';
                showDenyReasonValidation.value = false;
            },
            onFinish: () => {
                statusActionProcessingPaymentId.value = null;
            },
        },
    );
};
</script>

<template>
    <Head :title="__('Financial Ledger')" />

    <AdminLayout>
        <div class="animate-fade-in space-y-8">
            <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                <div>
                    <h2 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ __('Financial Ledger') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Track all incoming ecosystem cash flow and receipts.') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <div
                        class="flex items-center rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-2 text-sm font-bold text-indigo-600 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        <History :size="16" class="me-2" /> Sync Data Live
                    </div>
                </div>
            </div>

            <div
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 text-center shadow-sm dark:border-slate-700/50 dark:bg-slate-800"
                v-if="!payments?.length"
            >
                <CreditCard class="mx-auto mb-4 h-16 w-16 text-slate-300 dark:text-slate-600" />
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('No Transactions Found') }}</h3>
                <p class="mx-auto mt-2 mb-6 max-w-sm text-sm text-slate-500">
                    {{ __('Once a customer subscribes from the landing page or portal, their payment record will be logged here.') }}
                </p>
            </div>

            <div v-if="paymentRows.length" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-800">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-4 dark:border-slate-700/50">
                    <h3 class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white">
                        <CreditCard class="text-emerald-500" :size="20" />
                        <span>{{ __(paymentSectionTitle) }} ({{ filteredPaymentRows.length }})</span>
                    </h3>
                    <div class="flex w-full flex-col gap-2 lg:w-auto lg:flex-row lg:items-center">
                        <div class="relative w-full lg:w-72">
                            <Search :size="16" class="pointer-events-none absolute top-1/2 left-2.5 -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                :placeholder="__('Search transactions')"
                                class="h-10 w-full rounded-xl border border-slate-200 bg-white pr-3 pl-8 text-sm text-slate-700 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                            />
                        </div>

                        <div ref="customerDropdownRef" class="relative w-full lg:w-52">
                            <button
                                type="button"
                                class="inline-flex h-10 w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-3 text-left text-sm text-slate-700 transition-colors focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                @click="showCustomerDropdown ? (showCustomerDropdown = false) : openCustomerDropdown()"
                            >
                                <span class="truncate">{{ selectedCustomerLabel }}</span>
                                <ChevronDown :size="16" class="ms-2 shrink-0 text-slate-500" :class="showCustomerDropdown ? 'rotate-180' : ''" />
                            </button>

                            <div
                                v-if="showCustomerDropdown"
                                class="absolute top-full right-0 left-0 z-30 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
                            >
                                <div class="border-b border-slate-200 p-2 dark:border-slate-700">
                                    <div class="relative">
                                        <Search :size="16" class="pointer-events-none absolute top-1/2 left-2.5 -translate-y-1/2 text-slate-400" />
                                        <input
                                            ref="customerSearchInputRef"
                                            v-model="customerSearchQuery"
                                            type="text"
                                            :placeholder="__('Search customer')"
                                            class="h-10 w-full rounded-lg border border-slate-200 bg-white pr-3 pl-8 text-sm text-slate-700 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                                            @keydown.esc.prevent="showCustomerDropdown = false"
                                        />
                                    </div>
                                </div>

                                <div class="max-h-56 overflow-y-auto p-1">
                                    <button
                                        type="button"
                                        class="w-full rounded-lg px-2.5 py-2 text-left transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                                        :class="customerId === '' ? 'bg-primary-50 dark:bg-primary-500/10' : ''"
                                        @click="clearCustomerFilter"
                                    >
                                        <p class="truncate text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('All Customers') }}</p>
                                    </button>
                                    <button
                                        v-for="customer in filteredCustomers"
                                        :key="customer.id"
                                        type="button"
                                        class="w-full rounded-lg px-2.5 py-2 text-left transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                                        :class="customerId === `${customer.id}` ? 'bg-primary-50 dark:bg-primary-500/10' : ''"
                                        @click="selectCustomer(customer)"
                                    >
                                        <p class="truncate text-sm font-semibold text-slate-700 dark:text-slate-200">{{ customer.name }}</p>
                                    </button>
                                    <p v-if="!filteredCustomers.length" class="px-2 py-4 text-center text-xs font-medium text-slate-500">
                                        {{ __('No customer found.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div ref="planDropdownRef" class="relative w-full lg:w-52">
                            <button
                                type="button"
                                class="inline-flex h-10 w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-3 text-left text-sm text-slate-700 transition-colors focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                @click="showPlanDropdown ? (showPlanDropdown = false) : openPlanDropdown()"
                            >
                                <span class="truncate">{{ selectedPlanLabel }}</span>
                                <ChevronDown :size="16" class="ms-2 shrink-0 text-slate-500" :class="showPlanDropdown ? 'rotate-180' : ''" />
                            </button>

                            <div
                                v-if="showPlanDropdown"
                                class="absolute top-full right-0 left-0 z-30 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
                            >
                                <div class="border-b border-slate-200 p-2 dark:border-slate-700">
                                    <div class="relative">
                                        <Search :size="16" class="pointer-events-none absolute top-1/2 left-2.5 -translate-y-1/2 text-slate-400" />
                                        <input
                                            ref="planSearchInputRef"
                                            v-model="planSearchQuery"
                                            type="text"
                                            :placeholder="__('Search plan')"
                                            class="h-10 w-full rounded-lg border border-slate-200 bg-white pr-3 pl-8 text-sm text-slate-700 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                                            @keydown.esc.prevent="showPlanDropdown = false"
                                        />
                                    </div>
                                </div>

                                <div class="max-h-56 overflow-y-auto p-1">
                                    <button
                                        type="button"
                                        class="w-full rounded-lg px-2.5 py-2 text-left transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                                        :class="planId === '' ? 'bg-primary-50 dark:bg-primary-500/10' : ''"
                                        @click="clearPlanFilter"
                                    >
                                        <p class="truncate text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('All Plans') }}</p>
                                    </button>
                                    <button
                                        v-for="plan in filteredPlans"
                                        :key="plan.id"
                                        type="button"
                                        class="w-full rounded-lg px-2.5 py-2 text-left transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                                        :class="planId === `${plan.id}` ? 'bg-primary-50 dark:bg-primary-500/10' : ''"
                                        @click="selectPlan(plan)"
                                    >
                                        <p class="truncate text-sm font-semibold text-slate-700 dark:text-slate-200">{{ plan.name }}</p>
                                    </button>
                                    <p v-if="!filteredPlans.length" class="px-2 py-4 text-center text-xs font-medium text-slate-500">
                                        {{ __('No plan found.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="resetFilters"
                            :disabled="!hasActiveFilters"
                            class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-bold tracking-widest text-slate-700 uppercase transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            {{ __('Reset') }}
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50">
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Txn Ref') }}</th>
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Member') }}</th>
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Plan') }}</th>
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Amount') }}</th>
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Method') }}</th>
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Type') }}</th>
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Status') }}</th>
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Timestamp') }}</th>
                                <th v-if="showActionColumn" class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr
                                v-for="payment in filteredPaymentRows"
                                :key="payment.id"
                                class="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-800/50"
                            >
                                <td class="p-4">
                                    <span class="font-mono text-xs text-slate-500">PAY-{{ payment.id.toString().padStart(6, '0') }}</span>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-700"
                                        >
                                            <User :size="14" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">
                                                {{ payment.subscription?.user?.name || 'Unknown' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        {{ payment.subscription?.plan?.name || __('N/A') }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="text-base font-black text-emerald-600 dark:text-emerald-400">{{ formatCurrency(payment.amount) }}</span>
                                </td>
                                <td class="p-4">
                                    <span
                                        class="inline-flex items-center rounded border border-slate-200 px-2 py-1 text-xs tracking-wider text-slate-600 uppercase dark:border-slate-700 dark:text-slate-300"
                                    >
                                        {{ payment.method || 'Credit/Stripe' }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold uppercase"
                                        :class="
                                            payment.transaction_type === 'renew'
                                                ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300'
                                                : payment.transaction_type === 'new'
                                                  ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                                                  : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200'
                                        "
                                    >
                                        {{ formatTransactionType(payment.transaction_type) }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span
                                        class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-bold"
                                        :class="statusPillClass(payment.status)"
                                        :title="paymentStatusTooltip(payment)"
                                    >
                                        {{ payment.status }}
                                    </span>
                                </td>
                                <td class="p-4 text-sm text-slate-500">
                                    {{ formatDate(payment.created_at) }}
                                </td>
                                <td v-if="showActionColumn" class="p-4">
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex h-8 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 text-xs font-bold text-emerald-700 transition-colors hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20"
                                            :disabled="statusActionProcessingPaymentId === Number(payment.id)"
                                            @click="openApproveConfirm(payment)"
                                        >
                                            {{ statusActionProcessingPaymentId === Number(payment.id) ? __('Processing...') : __('Approve') }}
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-8 items-center rounded-lg border border-red-200 bg-red-50 px-2.5 text-xs font-bold text-red-700 transition-colors hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300 dark:hover:bg-red-500/20"
                                            :disabled="statusActionProcessingPaymentId === Number(payment.id)"
                                            @click="openDenyReasonDialog(payment)"
                                        >
                                            {{ __('Deny') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!filteredPaymentRows.length">
                                <td :colspan="tableColumnCount" class="p-8 text-center text-sm text-slate-500 italic">
                                    {{ __('No matching transactions found.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="showApproveConfirmModal"
            title="Approve this payment?"
            message="The related membership will be activated or renewed immediately."
            confirm-text="Yes, Approve"
            cancel-text="Cancel"
            type="warning"
            :processing="actionModalProcessing"
            @close="closeApproveConfirm"
            @confirm="submitPaymentStatusAction('approve')"
        />

        <div v-if="showDenyReasonModal" class="fixed inset-0 z-[170] flex items-center justify-center p-4 sm:p-6">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeDenyReasonDialog"></div>
            <div class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <div>
                        <p class="text-xs font-bold tracking-widest text-red-500 uppercase">{{ __('Payment Action') }}</p>
                        <h3 class="mt-1 text-base font-bold text-slate-900 dark:text-white">{{ __('Deny Payment') }}</h3>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-700 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                        @click="closeDenyReasonDialog"
                    >
                        <X :size="16" />
                    </button>
                </div>

                <div class="space-y-3 px-5 py-4">
                    <label class="text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Reason') }}</label>
                    <textarea
                        v-model="denialReason"
                        rows="4"
                        maxlength="500"
                        :placeholder="__('Enter denial reason')"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-red-500 focus:ring-2 focus:ring-red-500/15 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                    ></textarea>
                    <p v-if="denyReasonValidationMessage" class="text-xs font-medium text-red-600 dark:text-red-400">
                        {{ denyReasonValidationMessage }}
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-5 py-4 dark:border-slate-700">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-bold text-slate-600 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                        :disabled="actionModalProcessing"
                        @click="closeDenyReasonDialog"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-red-600 px-3.5 py-2 text-sm font-bold text-white transition-colors hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="actionModalProcessing"
                        @click="submitPaymentStatusAction('deny')"
                    >
                        {{ actionModalProcessing ? __('Processing...') : __('Confirm Deny') }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
