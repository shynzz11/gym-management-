<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { Activity, CalendarClock, ChevronDown, ChevronLeft, ChevronRight, Dumbbell, Search, Wallet, X, Zap } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import PlanFeatureList from '@/Components/Common/PlanFeatureList.vue';
import useCurrency from '@/Composables/useCurrency';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import customer from '@/routes/customer';

type PaymentGateway = 'sslcommerz' | 'stripe' | 'cash';

type PaymentOptions = {
    currency_code?: string;
    sslcommerz?: boolean;
    stripe?: boolean;
    cash?: boolean;
    default_gateway?: string;
};

const props = defineProps<{
    activeSubscriptions: any[];
    subscribedPlanIds?: number[];
    pendingRenewPlanIds?: number[];
    availablePlans: any[];
    payments: any[];
    paymentOptions?: PaymentOptions;
}>();

const page = usePage();
const { formatCurrency } = useCurrency();
const selectedSubscriptionIndex = ref(0);
const showPlanActionModal = ref(false);
const planActionType = ref<'renew' | 'switch'>('renew');
const showJoinPlanDropdown = ref(false);
const joinPlanSearchQuery = ref('');
const joinPlanDropdownRef = ref<HTMLElement | null>(null);
const joinPlanSearchInputRef = ref<HTMLInputElement | null>(null);
const planRequestForm = useForm({
    plan_id: '',
    payment_gateway: '',
    action: 'renew',
});

const subscriptions = computed(() => props.activeSubscriptions ?? []);
const hasSubscriptions = computed(() => subscriptions.value.length > 0);
const hasAnySubscription = computed(() => (props.subscribedPlanIds ?? []).length > 0);
const hasMultipleSubscriptions = computed(() => subscriptions.value.length > 1);
const availablePlans = computed(() => props.availablePlans ?? []);
const subscribedPlanIdSet = computed(() => {
    return new Set(
        (props.subscribedPlanIds ?? [])
            .map((planId) => Number(planId))
            .filter((planId) => Number.isFinite(planId)),
    );
});
const pendingRenewPlanIdSet = computed(() => {
    return new Set(
        (props.pendingRenewPlanIds ?? [])
            .map((planId) => Number(planId))
            .filter((planId) => Number.isFinite(planId)),
    );
});
const activePlanIds = computed(() => {
    return new Set(
        subscriptions.value
            .map((subscription) => Number(subscription.plan_id))
            .filter((planId) => Number.isFinite(planId)),
    );
});
const joinablePlans = computed(() => {
    return availablePlans.value.filter((plan) => {
        return !activePlanIds.value.has(Number(plan.id));
    });
});
const filteredJoinablePlans = computed(() => {
    const query = joinPlanSearchQuery.value.trim().toLowerCase();

    if (!query) {
        return joinablePlans.value;
    }

    return joinablePlans.value.filter((plan) => {
        return `${plan.name} ${plan.duration_days} ${formatCurrency(plan.price)}`.toLowerCase().includes(query);
    });
});
const isOfferActive = (plan: any): boolean => {
    if (!plan?.is_offer || !plan?.offer_ends_at) {
        return false;
    }

    return new Date(plan.offer_ends_at) > new Date();
};

const selectedSubscription = computed(() => {
    if (!subscriptions.value.length) {
        return null;
    }

    return subscriptions.value[selectedSubscriptionIndex.value] ?? subscriptions.value[0];
});
const normalizeFacilities = (facilities: unknown): string[] => {
    const normalizedFacilities = Array.isArray(facilities) ? facilities : [];

    return normalizedFacilities
        .map((facility) => String(facility).trim())
        .filter((facility) => facility.length > 0)
        .filter((facility, index, allFacilities) => {
            return allFacilities.findIndex((item) => item.toLowerCase() === facility.toLowerCase()) === index;
        });
};
const selectedPlanFacilities = computed(() => {
    return normalizeFacilities(selectedSubscription.value?.plan?.facilities);
});
const isRenewAction = computed(() => planActionType.value === 'renew');
const renewPlanDisplayLabel = computed(() => {
    const plan = selectedSubscription.value?.plan;

    if (!plan) {
        return '-';
    }

    const priceLabel = isOfferActive(plan)
        ? `${formatCurrency(plan.offer_price)} (Offer)`
        : formatCurrency(plan.price);

    return `${plan.name} - ${plan.duration_days} days - ${priceLabel}`;
});
const canSubmitPlanAction = computed(() => {
    if (planRequestForm.processing) {
        return false;
    }

    if (!planRequestForm.payment_gateway) {
        return false;
    }

    if (isRenewAction.value) {
        return Boolean(selectedSubscription.value?.plan_id);
    }

    return Boolean(planRequestForm.plan_id);
});
const checkoutPlan = computed(() => {
    if (isRenewAction.value) {
        return selectedSubscription.value?.plan ?? null;
    }

    return joinablePlans.value.find((plan) => `${plan.id}` === `${planRequestForm.plan_id}`) ?? null;
});
const selectedJoinPlan = computed(() => {
    return joinablePlans.value.find((plan) => `${plan.id}` === `${planRequestForm.plan_id}`) ?? null;
});
const selectedJoinPlanLabel = computed(() => {
    if (!selectedJoinPlan.value) {
        return 'Select a plan';
    }

    const renewBadge = subscribedPlanIdSet.value.has(Number(selectedJoinPlan.value.id)) ? ' (Renew)' : '';

    const priceLabel = isOfferActive(selectedJoinPlan.value)
        ? `${formatCurrency(selectedJoinPlan.value.offer_price)} (Offer)`
        : formatCurrency(selectedJoinPlan.value.price);

    return `${selectedJoinPlan.value.name} - ${selectedJoinPlan.value.duration_days} days - ${priceLabel}${renewBadge}`;
});
const checkoutPlanFacilities = computed(() => {
    return normalizeFacilities(checkoutPlan.value?.facilities);
});
const effectivePlanAction = computed<'renew' | 'switch'>(() => {
    if (isRenewAction.value) {
        return 'renew';
    }

    const selectedPlanId = Number(planRequestForm.plan_id);
    if (!Number.isFinite(selectedPlanId) || selectedPlanId <= 0) {
        return 'switch';
    }

    return subscribedPlanIdSet.value.has(selectedPlanId) ? 'renew' : 'switch';
});
const submitPlanActionLabel = computed(() => {
    if (planRequestForm.processing) {
        return 'Submitting...';
    }

    return effectivePlanAction.value === 'renew' ? 'Renew Plan' : 'Join Plan';
});
const checkoutAmountBdt = computed(() => {
    const plan = checkoutPlan.value;
    if (!plan) {
        return 0;
    }

    if (isOfferActive(plan)) {
        return Number(plan.offer_price);
    }

    return Number(plan.price);
});
const isFreeCheckoutPlan = computed(() => checkoutAmountBdt.value <= 0);
const systemCurrencyCode = computed(() => {
    return String(props.paymentOptions?.currency_code ?? 'INR').trim().toUpperCase();
});
const paymentGatewayAvailability = computed<Required<PaymentOptions>>(() => {
    const isBdtCurrency = systemCurrencyCode.value === 'BDT';

    return {
        currency_code: systemCurrencyCode.value,
        sslcommerz: Boolean(props.paymentOptions?.sslcommerz ?? true) && isBdtCurrency,
        stripe: Boolean(props.paymentOptions?.stripe ?? true) && !isBdtCurrency,
        cash: true,
        default_gateway: String(props.paymentOptions?.default_gateway ?? ''),
    };
});
const enabledPaymentGateways = computed<PaymentGateway[]>(() => {
    if (isFreeCheckoutPlan.value) {
        return ['cash'];
    }

    const gatewayList: PaymentGateway[] = [];

    if (paymentGatewayAvailability.value.sslcommerz) {
        gatewayList.push('sslcommerz');
    }

    if (paymentGatewayAvailability.value.stripe) {
        gatewayList.push('stripe');
    }

    if (paymentGatewayAvailability.value.cash) {
        gatewayList.push('cash');
    }

    return gatewayList;
});
const suggestedGateway = computed<PaymentGateway>(() => {
    if (isFreeCheckoutPlan.value) {
        return 'cash';
    }

    const configuredDefault = String(paymentGatewayAvailability.value.default_gateway ?? '') as PaymentGateway;
    if (enabledPaymentGateways.value.includes(configuredDefault)) {
        return configuredDefault;
    }

    if (paymentGatewayAvailability.value.sslcommerz) {
        return 'sslcommerz';
    }

    if (paymentGatewayAvailability.value.stripe) {
        return 'stripe';
    }

    if (paymentGatewayAvailability.value.sslcommerz) {
        return 'sslcommerz';
    }

    return 'cash';
});

watch(
    subscriptions,
    (items) => {
        if (selectedSubscriptionIndex.value >= items.length) {
            selectedSubscriptionIndex.value = 0;
        }
    },
    { immediate: true },
);

const previousCard = (): void => {
    if (!hasMultipleSubscriptions.value) {
        return;
    }

    selectedSubscriptionIndex.value =
        selectedSubscriptionIndex.value === 0
            ? subscriptions.value.length - 1
            : selectedSubscriptionIndex.value - 1;
};

const nextCard = (): void => {
    if (!hasMultipleSubscriptions.value) {
        return;
    }

    selectedSubscriptionIndex.value = (selectedSubscriptionIndex.value + 1) % subscriptions.value.length;
};

const selectCard = (index: number): void => {
    selectedSubscriptionIndex.value = index;
};

const formatDate = (date: string): string => {
    return date ? dayjs(date).format('MMM D, YYYY') : '-';
};

const formatTransactionType = (value: string | null | undefined): string => {
    if (value === 'renew') {
        return 'Renew';
    }

    return 'New';
};

const transactionTypeClass = (value: string | null | undefined): string => {
    if (value === 'renew') {
        return 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300';
    }

    return 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300';
};

const paymentStatusClass = (value: string | null | undefined): string => {
    const normalizedStatus = `${value ?? ''}`.toLowerCase();

    if (normalizedStatus === 'completed' || normalizedStatus === 'processed' || normalizedStatus === 'approved') {
        return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-400';
    }

    if (normalizedStatus === 'canceled' || normalizedStatus === 'cancelled' || normalizedStatus === 'denied' || normalizedStatus === 'failed') {
        return 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300';
    }

    return 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-400';
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

const getDaysRemaining = (subscription: any): number => {
    const diff = dayjs(subscription.end_date).endOf('day').diff(dayjs(), 'day');

    return diff > 0 ? diff : 0;
};

const getExpiryCountdown = (subscription: any): string => {
    const remainingDays = getDaysRemaining(subscription);

    if (remainingDays <= 0) {
        return 'Expired';
    }

    if (remainingDays === 1) {
        return '1 day left';
    }

    return `${remainingDays} days left`;
};

const getProgress = (subscription: any): number => {
    const start = dayjs(subscription.start_date);
    const end = dayjs(subscription.end_date);

    if (!start.isValid() || !end.isValid()) {
        return 0;
    }

    const totalDays = Math.max(end.diff(start, 'day'), 1);
    const elapsedDays = Math.max(dayjs().diff(start, 'day'), 0);
    const progress = (elapsedDays / totalDays) * 100;

    if (progress > 100) {
        return 100;
    }

    return progress;
};

const getAccessLabel = (subscription: any): string => {
    const accessType = String(subscription?.plan?.access_type ?? 'anytime').replaceAll('_', ' ');
    const normalizedAccessType = accessType
        .split(' ')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

    const start = subscription?.plan?.start_time;
    const end = subscription?.plan?.end_time;

    if (!start || !end) {
        return normalizedAccessType;
    }

    const startTime = dayjs(`2000-01-01 ${start}`).format('h:mm A');
    const endTime = dayjs(`2000-01-01 ${end}`).format('h:mm A');

    return `${normalizedAccessType} (${startTime} - ${endTime})`;
};

const getPlanAccessLabel = (plan: any): string => {
    const accessType = String(plan?.access_type ?? 'anytime').replaceAll('_', ' ');
    const normalizedAccessType = accessType
        .split(' ')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
    const start = plan?.start_time;
    const end = plan?.end_time;

    if (!start || !end) {
        return normalizedAccessType;
    }

    const startTime = dayjs(`2000-01-01 ${start}`).format('h:mm A');
    const endTime = dayjs(`2000-01-01 ${end}`).format('h:mm A');

    return `${normalizedAccessType} (${startTime} - ${endTime})`;
};

const getPlanTimeLabel = (subscription: any): string => {
    const start = subscription?.plan?.start_time;
    const end = subscription?.plan?.end_time;

    if (!start || !end) {
        return 'Any time';
    }

    const startTime = dayjs(`2000-01-01 ${start}`).format('h:mm A');
    const endTime = dayjs(`2000-01-01 ${end}`).format('h:mm A');

    return `${startTime} - ${endTime}`;
};

const getPlanTimeLabelFromPlan = (plan: any): string => {
    const start = plan?.start_time;
    const end = plan?.end_time;

    if (!start || !end) {
        return 'Any time';
    }

    const startTime = dayjs(`2000-01-01 ${start}`).format('h:mm A');
    const endTime = dayjs(`2000-01-01 ${end}`).format('h:mm A');

    return `${startTime} - ${endTime}`;
};

const paymentMethodLabel = (gateway: PaymentGateway): string => {
    return gateway === 'sslcommerz'
        ? 'SSLCommerz'
        : gateway === 'stripe'
          ? 'Stripe'
          : 'Cash';
};

const membershipIndicator = computed(() => {
    if (!selectedSubscription.value) {
        return hasAnySubscription.value ? 'Expired' : 'No Membership';
    }

    return getExpiryCountdown(selectedSubscription.value);
});
const hasSelectedPlanPendingRenewalRequest = computed(() => {
    const selectedPlanId = Number(selectedSubscription.value?.plan_id ?? 0);
    if (!Number.isFinite(selectedPlanId) || selectedPlanId <= 0) {
        return false;
    }

    return pendingRenewPlanIdSet.value.has(selectedPlanId);
});
const hasSelectedFreePlan = computed(() => {
    return Number(selectedSubscription.value?.plan?.price ?? 0) <= 0;
});

const qrPayloadFor = (subscription: any): string => {
    return JSON.stringify({
        user_id: (page.props as any).auth.user.id,
        subscription_id: subscription.id,
        status: subscription.status,
    });
};

const qrImageUrlFor = (subscription: any): string => {
    const payload = qrPayloadFor(subscription);

    return `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(payload)}`;
};

const openJoinPlanDropdown = (): void => {
    showJoinPlanDropdown.value = true;

    window.setTimeout(() => {
        joinPlanSearchInputRef.value?.focus();
    }, 0);
};

const closeJoinPlanDropdown = (): void => {
    showJoinPlanDropdown.value = false;
    joinPlanSearchQuery.value = '';
};

const selectJoinPlan = (plan: any): void => {
    planRequestForm.plan_id = `${plan.id}`;
    closeJoinPlanDropdown();
    planRequestForm.clearErrors('plan_id');
};

const handleJoinPlanDropdownOutsideClick = (event: MouseEvent): void => {
    const target = event.target as Node | null;

    if (!target || !showJoinPlanDropdown.value || !joinPlanDropdownRef.value) {
        return;
    }

    if (!joinPlanDropdownRef.value.contains(target)) {
        showJoinPlanDropdown.value = false;
    }
};

const openPlanActionModal = (action: 'renew' | 'switch'): void => {
    if (action === 'renew' && (hasSelectedPlanPendingRenewalRequest.value || hasSelectedFreePlan.value)) {
        return;
    }

    planActionType.value = action;
    planRequestForm.action = action;
    planRequestForm.payment_gateway = suggestedGateway.value;
    planRequestForm.plan_id = action === 'renew' && selectedSubscription.value
        ? `${selectedSubscription.value.plan_id}`
        : '';
    showJoinPlanDropdown.value = false;
    joinPlanSearchQuery.value = '';
    planRequestForm.clearErrors();
    showPlanActionModal.value = true;
};

const closePlanActionModal = (): void => {
    showPlanActionModal.value = false;
    closeJoinPlanDropdown();
    planRequestForm.clearErrors();
};

const submitPlanAction = (): void => {
    const planId = isRenewAction.value ? `${selectedSubscription.value?.plan_id ?? ''}` : planRequestForm.plan_id;

    if (!planId) {
        return;
    }

    planRequestForm
        .transform((data) => ({
            ...data,
            plan_id: Number(planId),
            payment_gateway: data.payment_gateway,
            action: effectivePlanAction.value,
        }))
        .post(customer.payments.checkout.url(), {
            preserveScroll: true,
            onSuccess: () => {
                closePlanActionModal();
            },
        });
};

watch(
    () => joinablePlans.value.map((plan) => Number(plan.id)),
    (planIds) => {
        if (planActionType.value !== 'switch') {
            return;
        }

        if (planRequestForm.plan_id && !planIds.includes(Number(planRequestForm.plan_id))) {
            planRequestForm.plan_id = '';
        }
    },
);

watch(
    enabledPaymentGateways,
    (gatewayList) => {
        if (!gatewayList.length) {
            planRequestForm.payment_gateway = '';

            return;
        }

        const currentGateway = planRequestForm.payment_gateway as PaymentGateway;
        if (!gatewayList.includes(currentGateway)) {
            planRequestForm.payment_gateway = gatewayList[0];
        }
    },
    { immediate: true },
);

onMounted(() => {
    document.addEventListener('click', handleJoinPlanDropdownOutsideClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleJoinPlanDropdownOutsideClick);
});
</script>

<template>
    <Head :title="__('Member Portal')" />

    <CustomerLayout>
        <div class="animate-fade-in relative z-10 mx-auto max-w-6xl space-y-8">
            <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                <div>
                    <h2 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ __('Welcome back!') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Manage your fitness journey and access the facility.') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <div class="lg:col-span-1">
                    <div
                        v-if="!hasSubscriptions"
                        class="relative flex h-full flex-col justify-between overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-white to-slate-50 p-6 text-slate-900 shadow-sm"
                    >
                        <div class="pointer-events-none absolute -top-16 -right-16 opacity-10">
                            <Zap :size="200" class="text-primary-500" />
                        </div>

                        <div>
                            <div class="mb-10 flex items-start justify-between">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-slate-100 backdrop-blur-sm opacity-80">
                                    <Dumbbell class="text-primary-500" :size="20" />
                                </div>
                                <span
                                    class="rounded-full border px-3 py-1 text-xs font-bold tracking-widest uppercase"
                                    :class="
                                        hasAnySubscription
                                            ? 'border-red-500/30 bg-red-500/20 text-red-400'
                                            : 'border-primary-500/30 bg-primary-500/20 text-primary-500'
                                    "
                                >
                                    {{ hasAnySubscription ? __('Inactive') : __('Explorer') }}
                                </span>
                            </div>

                            <p class="mb-1 text-sm tracking-widest text-slate-500 uppercase">{{ __('Digital Pass') }}</p>
                            <h3 class="mb-2 text-2xl font-black tracking-tight">{{ __('No Active Plan') }}</h3>
                            <p class="text-sm font-medium text-primary-500">{{ $page.props.auth.user.name }}</p>
                        </div>

                        <div v-if="hasAnySubscription" class="mt-10 border-t border-slate-200 pt-6">
                            <p class="text-xs text-red-400">{{ __('Membership Required') }}</p>
                        </div>
                        <div v-else class="mt-10 border-t border-slate-200 pt-6">
                            <p class="text-xs text-primary-500">{{ __('Welcome to our community') }}</p>
                        </div>
                    </div>

                    <div
                        v-else-if="!hasMultipleSubscriptions"
                        class="relative flex h-full flex-col justify-between overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-white to-slate-50 p-6 text-slate-900 shadow-sm"
                    >
                        <div class="pointer-events-none absolute -top-16 -right-16 opacity-10">
                            <Zap :size="200" class="text-primary-500" />
                        </div>

                        <div>
                            <div class="mb-10 flex items-start justify-between">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-slate-100 backdrop-blur-sm">
                                    <Dumbbell class="text-primary-500" :size="20" />
                                </div>
                                <span class="flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/20 px-3 py-1 text-xs font-bold tracking-widest text-emerald-700 uppercase">
                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span> {{ __('Active') }}
                                </span>
                            </div>

                            <p class="mb-1 text-sm tracking-widest text-slate-500 uppercase">{{ __('Digital Membership Card') }}</p>
                            <h3 class="mb-2 text-2xl font-black tracking-tight">{{ selectedSubscription?.plan?.name }}</h3>
                            <p class="text-xs font-semibold text-slate-500">{{ getAccessLabel(selectedSubscription) }}</p>
                            <div class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-primary-200 bg-primary-50 px-3 py-1 text-[11px] font-semibold text-primary-700">
                                <CalendarClock :size="13" class="text-primary-500" />
                                <span>{{ __('Plan Time') }}: {{ getPlanTimeLabel(selectedSubscription) }}</span>
                            </div>
                            <p class="mt-2 text-sm font-medium text-primary-500">{{ getExpiryCountdown(selectedSubscription) }}</p>
                        </div>

                        <div class="mt-10 flex items-center justify-between border-t border-slate-200 pt-6">
                            <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                <img :src="qrImageUrlFor(selectedSubscription)" :alt="__('Membership QR')" class="h-20 w-20 rounded-lg object-cover" />
                            </div>
                            <div class="ml-4 flex-1 text-right">
                                <p class="mb-1 text-xs tracking-widest text-slate-500 uppercase">{{ __('Scan to enter') }}</p>
                                <p class="font-mono text-[10px] text-slate-500">
                                    {{ $page.props.auth.user.id }}-{{ selectedSubscription.plan_id }}-{{ new Date().getFullYear() }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-white to-slate-50 p-6 text-slate-900 shadow-sm"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <p class="text-xs font-black tracking-widest text-slate-500 uppercase">{{ __('Membership Cards') }}</p>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="previousCard"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-50"
                                >
                                    <ChevronLeft :size="16" />
                                </button>
                                <button
                                    type="button"
                                    @click="nextCard"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-50"
                                >
                                    <ChevronRight :size="16" />
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="selectedSubscription"
                            class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-extrabold text-slate-900">{{ selectedSubscription.plan?.name }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">{{ getAccessLabel(selectedSubscription) }}</p>
                                </div>
                                <span class="rounded-full border border-emerald-500/30 bg-emerald-500/20 px-2.5 py-1 text-[10px] font-bold tracking-widest text-emerald-700 uppercase">
                                    {{ __('Active') }}
                                </span>
                            </div>

                            <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">{{ __('Expiry Countdown') }}</p>
                                    <p class="mt-1 text-sm leading-snug font-bold text-slate-900">{{ getExpiryCountdown(selectedSubscription) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">{{ __('End Date') }}</p>
                                    <p class="mt-1 text-sm leading-snug font-bold text-slate-900">{{ formatDate(selectedSubscription.end_date) }}</p>
                                </div>
                                <div class="rounded-xl border border-primary-200 bg-primary-50 p-3 sm:col-span-2">
                                    <p class="flex items-center gap-1 text-[10px] font-bold tracking-widest text-primary-600 uppercase">
                                        <CalendarClock :size="12" />
                                        <span>{{ __('Plan Time') }}</span>
                                    </p>
                                    <p class="mt-1 text-sm leading-snug font-bold text-slate-900">{{ getPlanTimeLabel(selectedSubscription) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-center gap-2">
                            <button
                                v-for="(subscription, index) in subscriptions"
                                :key="subscription.id"
                                type="button"
                                @click="selectCard(index)"
                                class="h-2.5 rounded-full transition-all"
                                :class="selectedSubscriptionIndex === index ? 'w-7 bg-primary-400' : 'w-2.5 bg-slate-300 hover:bg-slate-400'"
                            ></button>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col space-y-8 lg:col-span-2">
                    <div class="flex-1 rounded-3xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-700/50 dark:bg-slate-800">
                        <div class="mb-8 flex items-center gap-3 font-bold text-slate-800 dark:text-white">
                            <Activity class="text-primary-500" :size="20" /> {{ __('Membership Progress') }}
                        </div>

                        <div v-if="selectedSubscription">
                            <div class="mb-4 flex items-end justify-between">
                                <div>
                                    <p class="text-[11px] font-bold tracking-widest text-slate-400 uppercase">{{ __('Membership Status') }}</p>
                                    <p class="mt-1 text-4xl font-black text-slate-900 dark:text-white">{{ membershipIndicator }}</p>
                                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ getAccessLabel(selectedSubscription) }}</p>
                                </div>
                                <div class="hidden text-right sm:block">
                                    <p class="text-[11px] font-bold tracking-widest text-slate-400 uppercase">{{ __('Renews') }}</p>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ formatDate(selectedSubscription.end_date) }}</p>
                                </div>
                            </div>

                            <div class="mt-4 h-4 w-full overflow-hidden rounded-full border border-slate-200 bg-slate-100 shadow-inner dark:border-slate-700/50 dark:bg-slate-900">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-primary-400 to-primary-600 transition-all duration-1000 ease-out"
                                    :style="{ width: `${getProgress(selectedSubscription)}%` }"
                                ></div>
                            </div>

                            <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <button
                                    type="button"
                                    @click="openPlanActionModal('renew')"
                                    :disabled="hasSelectedPlanPendingRenewalRequest || hasSelectedFreePlan"
                                    class="h-11 rounded-xl bg-primary-600 px-4 text-sm font-bold text-white transition-colors hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {{
                                        hasSelectedFreePlan
                                            ? __('Free Plan')
                                            : hasSelectedPlanPendingRenewalRequest
                                              ? __('Renew Pending')
                                              : __('Renew Plan')
                                    }}
                                </button>
                                <button
                                    type="button"
                                    @click="openPlanActionModal('switch')"
                                    class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                >
                                    {{ __('Join Plan') }}
                                </button>
                            </div>

                            <p v-if="hasSelectedFreePlan" class="mt-3 text-xs font-medium text-slate-500 dark:text-slate-400">
                                {{ __('Free plans are one-time only and cannot be renewed.') }}
                            </p>

                            <p v-else-if="hasSelectedPlanPendingRenewalRequest" class="mt-3 text-xs font-medium text-amber-600 dark:text-amber-400">
                                {{ __('A renewal payment is pending. You can renew again after it is approved or canceled.') }}
                            </p>

                            <div v-if="selectedPlanFacilities.length > 0" class="mt-8 border-t border-slate-200 pt-6 dark:border-slate-700/50">
                                <p class="mb-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Plan Facilities') }}</p>
                                <PlanFeatureList :features="selectedPlanFacilities" :show-only-included="true" />
                            </div>
                        </div>

                        <div v-else class="py-8 text-center">
                            <p class="mb-6 text-sm text-slate-500">
                                {{
                                    hasAnySubscription
                                        ? __('You currently do not have an active membership.')
                                        : __('Get started on your fitness journey today!')
                                }}
                            </p>
                            <button
                                type="button"
                                @click="openPlanActionModal('switch')"
                                class="inline-flex h-11 items-center justify-center rounded-xl bg-primary-600 px-6 text-sm font-bold text-white shadow-lg shadow-primary-500/20 transition-colors hover:bg-primary-700"
                            >
                                {{ __('Join Plan & Pay') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8 dark:border-slate-700/50 dark:bg-slate-800">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-white">
                        <Wallet class="text-primary-500" :size="20" /> {{ __('Transactions History') }}
                    </h3>
                    <Link :href="customer.transactions.index.url()" class="text-xs font-bold text-primary-600 hover:text-primary-700">
                        {{ __('View all') }}
                    </Link>
                </div>

                <div v-if="payments?.length" class="space-y-4">
                    <div
                        v-for="payment in payments"
                        :key="payment.id"
                        class="flex flex-col justify-between gap-4 rounded-2xl border border-slate-100 bg-slate-50/50 p-4 transition-colors hover:bg-slate-50 sm:flex-row sm:items-center dark:border-slate-700 dark:bg-slate-900/30 dark:hover:bg-slate-900/50"
                    >
                        <div class="flex items-center gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-500 dark:bg-indigo-500/10">
                                <CalendarClock :size="18" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ payment.subscription?.plan?.name ?? 'GymOS Membership' }}
                                </p>
                                <p class="text-xs text-slate-500">{{ formatDate(payment.created_at) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-6 sm:w-auto sm:px-4">
                            <span
                                class="inline-flex items-center rounded px-2 py-0.5 text-[10px] font-bold tracking-widest uppercase"
                                :class="transactionTypeClass(payment.transaction_type)"
                            >
                                {{ formatTransactionType(payment.transaction_type) }}
                            </span>
                            <span
                                class="inline-flex items-center rounded px-2 py-0.5 text-[10px] font-bold tracking-widest uppercase"
                                :class="paymentStatusClass(payment.status)"
                                :title="paymentStatusTooltip(payment)"
                            >
                                {{ payment.status }}
                            </span>
                            <span class="text-lg font-black text-slate-900 dark:text-white">{{ formatCurrency(payment.amount) }}</span>
                        </div>
                    </div>
                </div>
                <div
                    v-else
                    class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 py-6 text-center dark:border-slate-700 dark:bg-slate-900/50"
                >
                    <p class="text-sm text-slate-500 italic">{{ __('No billing history available.') }}</p>
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
                    <div v-if="showPlanActionModal" class="fixed inset-0 z-[160] flex items-center justify-center p-4 sm:p-6">
                        <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" @click="closePlanActionModal"></div>

                        <form
                            class="relative flex h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
                            @submit.prevent="submitPlanAction"
                        >
                            <div class="shrink-0 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-6 py-5 dark:border-slate-700 dark:from-slate-900 dark:to-slate-900">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-black tracking-widest text-primary-600 uppercase dark:text-primary-300">{{ __('Plan Actions') }}</p>
                                        <h3 class="mt-1 text-xl font-bold text-slate-900 dark:text-white">
                                            {{ planActionType === 'renew' ? __('Renew Your Plan') : __('Join New Plan') }}
                                        </h3>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                            {{
                                                planActionType === 'renew'
                                                    ? __('Review your current plan and choose payment method to continue.')
                                                    : __('Choose a plan, review all details, and continue to payment.')
                                            }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                                        @click="closePlanActionModal"
                                    >
                                        <X :size="18" />
                                    </button>
                                </div>
                            </div>

                            <div class="flex-1 space-y-6 overflow-y-auto px-6 py-5">
                                <div v-if="planActionType === 'renew'">
                                    <label class="mb-1.5 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Plan') }}</label>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-800 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-100">
                                        {{ renewPlanDisplayLabel }}
                                    </div>
                                </div>

                                <div v-else>
                                    <label class="mb-1.5 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Plan') }}</label>
                                    <div ref="joinPlanDropdownRef" class="relative">
                                        <button
                                            type="button"
                                            class="inline-flex h-11 w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-3 text-left text-sm text-slate-800 transition-colors focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                            @click="showJoinPlanDropdown ? (showJoinPlanDropdown = false) : openJoinPlanDropdown()"
                                        >
                                            <span class="truncate">{{ selectedJoinPlanLabel }}</span>
                                            <ChevronDown :size="16" class="ms-2 shrink-0 text-slate-500" :class="showJoinPlanDropdown ? 'rotate-180' : ''" />
                                        </button>

                                        <div
                                            v-if="showJoinPlanDropdown"
                                            class="absolute top-full right-0 left-0 z-30 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
                                        >
                                            <div class="border-b border-slate-200 p-2 dark:border-slate-700">
                                                <div class="relative">
                                                    <Search :size="16" class="pointer-events-none absolute top-1/2 left-2.5 -translate-y-1/2 text-slate-400" />
                                                    <input
                                                        ref="joinPlanSearchInputRef"
                                                        v-model="joinPlanSearchQuery"
                                                        type="text"
                                                        :placeholder="__('Search plan by name, price, or duration')"
                                                        class="h-10 w-full rounded-lg border border-slate-300 bg-white pr-3 pl-8 text-sm text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                                        @keydown.esc.prevent="showJoinPlanDropdown = false"
                                                    />
                                                </div>
                                            </div>

                                            <div class="max-h-56 overflow-y-auto p-1">
                                                <button
                                                    v-for="plan in filteredJoinablePlans"
                                                    :key="plan.id"
                                                    type="button"
                                                    class="w-full rounded-lg px-2.5 py-2 text-left transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                                                    :class="`${planRequestForm.plan_id}` === `${plan.id}` ? 'bg-primary-50 dark:bg-primary-500/10' : ''"
                                                    @click="selectJoinPlan(plan)"
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
                                                        <span class="text-slate-500 dark:text-slate-400">{{ plan.duration_days }} {{ __('days') }}</span>
                                                        <span v-if="subscribedPlanIdSet.has(Number(plan.id))" class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                                            ({{ __('Renew') }})
                                                        </span>
                                                    </div>
                                                </button>
                                                <p v-if="!filteredJoinablePlans.length" class="px-2 py-4 text-center text-xs font-medium text-slate-500">
                                                    {{ __('No plan found.') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="joinablePlans.length === 0" class="mt-1.5 text-xs font-medium text-amber-600 dark:text-amber-400">
                                        {{ __('No additional active plans are available for assignment.') }}
                                    </p>
                                    <p v-if="planRequestForm.errors.plan_id" class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                                        {{ planRequestForm.errors.plan_id }}
                                    </p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Payment Method') }}</label>
                                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                                        <button
                                            v-for="gateway in enabledPaymentGateways"
                                            :key="gateway"
                                            type="button"
                                            class="rounded-xl border px-3 py-2.5 text-left transition-colors"
                                            :class="
                                                planRequestForm.payment_gateway === gateway
                                                    ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-200'
                                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800'
                                            "
                                            @click="planRequestForm.payment_gateway = gateway"
                                        >
                                            <p class="text-xs font-extrabold tracking-widest uppercase">{{ paymentMethodLabel(gateway) }}</p>
                                            <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                                                {{
                                                    isFreeCheckoutPlan
                                                        ? __('No payment required. This plan activates instantly.')
                                                        : gateway === 'sslcommerz'
                                                        ? __('Pay online with SSLCommerz')
                                                        : gateway === 'stripe'
                                                          ? __('Pay online with Stripe')
                                                          : __('Pay cash at gym desk')
                                                }}
                                            </p>
                                        </button>
                                    </div>
                                    <p v-if="planRequestForm.errors.payment_gateway" class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                                        {{ planRequestForm.errors.payment_gateway }}
                                    </p>
                                </div>

                                <div v-if="checkoutPlan" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-black tracking-widest text-primary-600 uppercase dark:text-primary-300">{{ __('Selected Plan') }}</p>
                                            <h4 class="mt-1 text-lg font-extrabold text-slate-900 dark:text-slate-100">{{ checkoutPlan.name }}</h4>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ getPlanAccessLabel(checkoutPlan) }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Duration') }}</p>
                                            <p class="text-sm font-extrabold text-slate-900 dark:text-slate-100">{{ checkoutPlan.duration_days }} {{ __('days') }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900">
                                            <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">{{ __('Price') }}</p>
                                            <div class="mt-1 flex items-center gap-2">
                                                <p v-if="isOfferActive(checkoutPlan)" class="text-sm font-extrabold text-orange-600 dark:text-orange-400">
                                                    {{ formatCurrency(checkoutPlan.offer_price) }}
                                                </p>
                                                <p 
                                                    class="text-sm font-extrabold text-slate-900 dark:text-white"
                                                    :class="{ 'text-xs opacity-50 line-through': isOfferActive(checkoutPlan) }"
                                                >
                                                    {{ formatCurrency(checkoutPlan.price) }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900">
                                            <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">{{ __('Plan Time') }}</p>
                                            <p class="mt-1 text-sm font-extrabold text-slate-900 dark:text-slate-100">{{ getPlanTimeLabelFromPlan(checkoutPlan) }}</p>
                                        </div>
                                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900">
                                            <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">{{ __('Currency') }}</p>
                                            <p class="mt-1 text-sm font-extrabold text-slate-900 dark:text-slate-100">{{ systemCurrencyCode }}</p>
                                        </div>
                                    </div>

                                    <p v-if="checkoutPlan.description" class="mt-4 text-sm text-slate-600 dark:text-slate-300">
                                        {{ checkoutPlan.description }}
                                    </p>

                                    <div v-if="checkoutPlanFacilities.length > 0" class="mt-4 border-t border-slate-200 pt-3 dark:border-slate-700">
                                        <p class="mb-2 text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Facilities') }}</p>
                                        <PlanFeatureList :features="checkoutPlanFacilities" :show-only-included="true" />
                                    </div>
                                </div>
                            </div>

                            <div class="shrink-0 border-t border-slate-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-900">
                                <div class="flex justify-end gap-3">
                                    <button
                                        type="button"
                                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                        @click="closePlanActionModal"
                                    >
                                        {{ __('Cancel') }}
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="!canSubmitPlanAction"
                                        class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        {{ submitPlanActionLabel }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </Transition>
            </Teleport>
        </div>
    </CustomerLayout>
</template>
