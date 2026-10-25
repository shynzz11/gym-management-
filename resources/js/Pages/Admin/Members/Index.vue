<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, PropType, ref, watch } from 'vue';
import admin from '@/routes/admin';
import { debounce } from 'lodash';
import { PlusCircle, Search, Crown, Shield, Pencil, Trash2, Eye, X } from 'lucide-vue-next';
import ConfirmationModal from '@/Components/Common/ConfirmationModal.vue';

const props = defineProps({
    users: Object as PropType<any>,
    plans: {
        type: Array as PropType<any[]>,
        default: () => [],
    },
    directoryType: {
        type: String as PropType<'staff' | 'customer'>,
        default: 'staff',
    },
    customerIndexUrl: String as PropType<string>,
});

const search = ref('');
const isCustomerDirectory = computed(() => props.directoryType === 'customer');
const hasUsers = computed(() => (props.users?.data ?? []).length > 0);
const directoryIndexUrl = computed(() => (isCustomerDirectory.value ? props.customerIndexUrl || admin.staff.index.url() : admin.staff.index.url()));
const tableColumnCount = computed(() => (isCustomerDirectory.value ? 5 : 4));
const memberCreateUrl = computed(() => admin.staff.create.url());
const customerCreateUrl = computed(() => admin.customers.create.url());

watch(
    search,
    debounce((value) => {
        router.get(directoryIndexUrl.value, { search: value }, { preserveState: true, replace: true });
    }, 300),
);

const getMembershipStatus = (user: any): 'active' | 'pending' | 'expired' | 'none' => {
    const latestSubscription = getLatestSubscription(user);

    if (!latestSubscription) {
        return 'none';
    }

    if (latestSubscription.status === 'pending') {
        return 'pending';
    }

    if (latestSubscription.status === 'expired') {
        return 'expired';
    }

    if (latestSubscription.status === 'active') {
        return 'active';
    }

    return 'none';
};

const getLatestSubscription = (user: any): any | null => {
    const subscriptions = user.subscriptions || [];

    if (!subscriptions.length) {
        return null;
    }

    return [...subscriptions].sort((a, b) => new Date(b.end_date).getTime() - new Date(a.end_date).getTime())[0];
};

const getCustomerSubscriptions = (user: any): any[] => {
    const subscriptions = Array.isArray(user?.subscriptions) ? [...user.subscriptions] : [];

    return subscriptions
        .filter((subscription) => subscription?.plan?.name)
        .sort((a, b) => {
            const firstDate = new Date(b.end_date ?? b.created_at ?? 0).getTime();
            const secondDate = new Date(a.end_date ?? a.created_at ?? 0).getTime();

            return firstDate - secondDate;
        });
};

const getVisibleCustomerSubscriptions = (user: any): any[] => {
    return getCustomerSubscriptions(user).slice(0, 2);
};

const getRemainingSubscriptionCount = (user: any): number => {
    const subscriptions = getCustomerSubscriptions(user);

    return subscriptions.length > 2 ? subscriptions.length - 2 : 0;
};

const formatSubscriptionStatus = (status: string | null | undefined): string => {
    if (!status) {
        return 'Unknown';
    }

    return status.charAt(0).toUpperCase() + status.slice(1);
};

const showCustomerDetailsModal = ref(false);
const selectedCustomer = ref<any | null>(null);

const openCustomerDetails = (user: any): void => {
    selectedCustomer.value = user;
    showCustomerDetailsModal.value = true;
};

const closeCustomerDetails = (): void => {
    showCustomerDetailsModal.value = false;
    selectedCustomer.value = null;
};

const customerPlanSubscriptions = computed(() => {
    if (!selectedCustomer.value) {
        return [];
    }

    const subscriptions = selectedCustomer.value.subscriptions || [];

    return [...subscriptions].sort((a, b) => new Date(b.end_date).getTime() - new Date(a.end_date).getTime());
});

const showAssignPlanModal = ref(false);
const customerForPlanAssignment = ref<any | null>(null);
const showStartDatePicker = ref(false);
const getTodayDate = (): string => {
    const date = new Date();
    const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60_000);

    return localDate.toISOString().slice(0, 10);
};
const assignPlanForm = useForm({
    user_id: '',
    plan_id: '',
    status: 'active',
    start_date: getTodayDate(),
});
const isStartDateInFuture = computed(() => assignPlanForm.start_date > getTodayDate());

const selectedPlanForAssignment = computed(() => {
    return (props.plans ?? []).find((plan) => `${plan.id}` === `${assignPlanForm.plan_id}`) ?? null;
});

const isRenewingSelectedPlan = computed((): boolean => {
    if (!customerForPlanAssignment.value || !assignPlanForm.plan_id) {
        return false;
    }

    const selectedPlanId = Number(assignPlanForm.plan_id);
    const subscriptions = Array.isArray(customerForPlanAssignment.value.subscriptions) ? customerForPlanAssignment.value.subscriptions : [];

    return subscriptions.some((subscription: any) => Number(subscription.plan_id ?? subscription.plan?.id) === selectedPlanId);
});

const selectedPlanFacilities = computed(() => {
    if (!selectedPlanForAssignment.value) {
        return [];
    }

    const facilities = selectedPlanForAssignment.value.facilities;

    if (!Array.isArray(facilities)) {
        return [];
    }

    return facilities
        .filter((facility) => typeof facility === 'string')
        .map((facility) => facility.trim())
        .filter((facility) => facility.length > 0);
});

const formatCurrency = (value: string | number | null | undefined): string => {
    const numeric = Number(value);

    if (Number.isNaN(numeric)) {
        return '0.00';
    }

    return numeric.toFixed(2);
};

const formatTimeWindow = (startTime?: string | null, endTime?: string | null): string => {
    if (!startTime || !endTime) {
        return 'Anytime';
    }

    const to12Hour = (time: string): string => {
        const [hourValue, minuteValue] = time.split(':');
        const hour = Number(hourValue);
        const minute = minuteValue ?? '00';
        const period = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;

        return `${hour12}:${minute} ${period}`;
    };

    return `${to12Hour(startTime)} - ${to12Hour(endTime)}`;
};

const openAssignPlanModal = (user: any): void => {
    if (!isCustomerDirectory.value) {
        return;
    }

    customerForPlanAssignment.value = user;
    assignPlanForm.reset();
    assignPlanForm.clearErrors();
    assignPlanForm.user_id = `${user.id}`;
    assignPlanForm.status = 'active';
    assignPlanForm.start_date = getTodayDate();
    showStartDatePicker.value = false;
    showAssignPlanModal.value = true;
};

const closeAssignPlanModal = (): void => {
    showAssignPlanModal.value = false;
    customerForPlanAssignment.value = null;
    showStartDatePicker.value = false;
    assignPlanForm.reset();
    assignPlanForm.clearErrors();
};

const submitAssignPlan = (): void => {
    if (!assignPlanForm.user_id || !assignPlanForm.plan_id) {
        return;
    }

    assignPlanForm
        .transform((data) => ({
            ...data,
            user_id: Number(data.user_id),
            plan_id: Number(data.plan_id),
        }))
        .post(admin.subscriptions.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                closeAssignPlanModal();
            },
        });
};

const formatDate = (date: string | null | undefined): string => {
    if (!date) {
        return '-';
    }

    const parsedDate = new Date(date);

    if (Number.isNaN(parsedDate.getTime())) {
        return '-';
    }

    return parsedDate.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const getStatusBadgeClass = (status: string): string => {
    if (status === 'active') {
        return 'border-emerald-200 bg-emerald-100 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/20 dark:text-emerald-300';
    }

    if (status === 'pending') {
        return 'border-amber-200 bg-amber-100 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/20 dark:text-amber-300';
    }

    if (status === 'expired') {
        return 'border-red-200 bg-red-100 text-red-700 dark:border-red-500/30 dark:bg-red-500/20 dark:text-red-300';
    }

    return 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-600 dark:bg-slate-700/60 dark:text-slate-300';
};

// Delete Logic
const showDeleteModal = ref(false);
const userToDelete = ref<any>(null);
const processingDelete = ref(false);

const confirmDelete = (user: any) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const handleDelete = () => {
    if (!userToDelete.value) return;

    processingDelete.value = true;
    router.delete(admin.staff.destroy.url(userToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            userToDelete.value = null;
        },
        onFinish: () => {
            processingDelete.value = false;
        },
    });
};
</script>

<template>
    <Head :title="__('Staff List')" />

    <AdminLayout>
        <div class="animate-fade-in space-y-6">
            <!-- Header -->
            <div class="flex flex-col items-start justify-between gap-6 text-start md:flex-row md:items-center">
                <div class="text-start">
                    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                        {{ isCustomerDirectory ? __('Customer List') : __('Staff List') }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{
                            isCustomerDirectory
                                ? __('Manage registered customers and membership activity.')
                                : __('Manage staff identities and access levels.')
                        }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <Link
                        v-if="!isCustomerDirectory"
                        :href="memberCreateUrl"
                        class="flex items-center rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white shadow-xl shadow-brand-600/20 transition-all hover:bg-brand-700 active:scale-95"
                    >
                        <PlusCircle :size="18" class="me-2" /> {{ __('Create Staff') }}
                    </Link>
                    <Link
                        v-if="isCustomerDirectory"
                        :href="customerCreateUrl"
                        class="flex items-center rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white shadow-xl shadow-brand-600/20 transition-all hover:bg-brand-700 active:scale-95"
                    >
                        <PlusCircle :size="18" class="me-2" /> {{ __('Create Customer') }}
                    </Link>
                </div>
            </div>

            <!-- Table Card -->
            <div class="overflow-hidden rounded-[2rem] border border-slate-200/60 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <!-- Search -->
                <div class="border-b border-slate-100 p-4 dark:border-slate-700">
                    <div class="relative w-full sm:w-64">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <Search :size="16" class="text-slate-400" />
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="isCustomerDirectory ? __('Search customers...') : __('Search staff...')"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pr-4 pl-10 text-sm text-slate-700 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-500/20 focus:outline-none dark:border-slate-700/60 dark:bg-slate-900/50 dark:text-slate-200 dark:placeholder:text-slate-500"
                        />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-start">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50 dark:border-slate-700/50 dark:bg-slate-800/20">
                                <th class="px-6 py-4 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase">
                                    {{ __('Identity') }}
                                </th>
                                <th class="px-6 py-4 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase">
                                    {{ isCustomerDirectory ? __('Phone') : __('Access Role') }}
                                </th>
                                <th
                                    v-if="isCustomerDirectory"
                                    class="px-6 py-4 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase"
                                >
                                    {{ __('Address') }}
                                </th>
                                <th
                                    v-if="isCustomerDirectory"
                                    class="px-6 py-4 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase"
                                >
                                    {{ __('Assigned Plans') }}
                                </th>
                                <th
                                    v-if="!isCustomerDirectory"
                                    class="px-6 py-4 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase"
                                >
                                    {{ __('Staff Status') }}
                                </th>
                                <th class="px-6 py-4 text-end text-[10px] font-bold tracking-widest text-slate-400 uppercase">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-start dark:divide-slate-800/50">
                            <template v-if="hasUsers">
                                <tr
                                    v-for="user in users.data"
                                    :key="user.id"
                                    class="group transition-all duration-200"
                                    :class="[
                                        user.subscriptions?.some((s: any) => s.status === 'expired')
                                            ? 'bg-red-50/50 hover:bg-red-50 dark:bg-red-900/10 dark:hover:bg-red-900/20'
                                            : 'hover:bg-slate-50/80 dark:hover:bg-slate-800/40',
                                    ]"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <img
                                                :src="
                                                    user.profile_image
                                                        ? '/storage/' + user.profile_image
                                                        : `https://ui-avatars.com/api/?name=${user.name}&background=random`
                                                "
                                                class="h-11 w-11 rounded-2xl bg-slate-100 object-cover dark:bg-slate-800"
                                            />
                                            <div>
                                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ user.name }}</p>
                                                <p class="text-[11px] text-slate-500">{{ user.email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td v-if="isCustomerDirectory" class="px-6 py-4">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                            {{ user.customer_profile?.phone || user.customerProfile?.phone || __('No phone') }}
                                        </span>
                                    </td>
                                    <td v-if="isCustomerDirectory" class="px-6 py-4">
                                        <span class="line-clamp-1 text-xs text-slate-500 dark:text-slate-400">
                                            {{ user.customer_profile?.address || user.customerProfile?.address || __('No address') }}
                                        </span>
                                    </td>
                                    <td v-if="isCustomerDirectory" class="px-6 py-4">
                                        <div v-if="getVisibleCustomerSubscriptions(user).length" class="space-y-2">
                                            <div
                                                v-for="subscription in getVisibleCustomerSubscriptions(user)"
                                                :key="subscription.id"
                                                class="flex items-center gap-2"
                                            >
                                                <span class="max-w-[11rem] truncate text-xs font-semibold text-slate-700 dark:text-slate-300">
                                                    {{ subscription.plan.name }}
                                                </span>
                                                <span
                                                    class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-bold tracking-wide uppercase"
                                                    :class="getStatusBadgeClass(subscription.status)"
                                                >
                                                    {{ formatSubscriptionStatus(subscription.status) }}
                                                </span>
                                            </div>
                                            <button
                                                v-if="getRemainingSubscriptionCount(user) > 0"
                                                type="button"
                                                @click="openCustomerDetails(user)"
                                                class="text-[11px] font-medium text-brand-600 hover:text-brand-700 hover:underline dark:text-brand-400 dark:hover:text-brand-300"
                                            >
                                                +{{ getRemainingSubscriptionCount(user) }} {{ __('more') }}
                                            </button>
                                        </div>
                                        <span v-else class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ __('No plan assigned') }}
                                        </span>
                                    </td>
                                    <td v-if="!isCustomerDirectory" class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-900/20"
                                            >
                                                <Crown
                                                    v-if="user.type === 'super-admin' || user.roles?.[0]?.name.toLowerCase().includes('admin')"
                                                    :size="14"
                                                />
                                                <Shield v-else :size="14" />
                                            </span>
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{
                                                user.type === 'super-admin' ? 'Super Admin' : user.roles?.[0]?.name || user.type
                                            }}</span>
                                        </div>
                                    </td>
                                    <td v-if="!isCustomerDirectory" class="px-6 py-4">
                                        <span
                                            v-if="getMembershipStatus(user) === 'active'"
                                            class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-100 px-2.5 py-1 text-[10px] font-bold tracking-widest text-emerald-700 uppercase dark:border-emerald-500/30 dark:bg-emerald-500/20 dark:text-emerald-300"
                                        >
                                            {{ __('Active') }}
                                        </span>
                                        <span
                                            v-else-if="getMembershipStatus(user) === 'pending'"
                                            class="inline-flex items-center rounded-full border border-amber-200 bg-amber-100 px-2.5 py-1 text-[10px] font-bold tracking-widest text-amber-700 uppercase dark:border-amber-500/30 dark:bg-amber-500/20 dark:text-amber-300"
                                        >
                                            {{ __('Pending') }}
                                        </span>
                                        <span
                                            v-else-if="getMembershipStatus(user) === 'expired'"
                                            class="inline-flex items-center rounded-full border border-red-200 bg-red-100 px-2.5 py-1 text-[10px] font-bold tracking-widest text-red-700 uppercase dark:border-red-500/30 dark:bg-red-500/20 dark:text-red-300"
                                        >
                                            {{ __('Expired') }}
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-[10px] font-bold tracking-widest text-slate-600 uppercase dark:border-slate-600 dark:bg-slate-700/60 dark:text-slate-300"
                                        >
                                            {{ __('No Plan') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-end">
                                        <div v-if="user.type !== 'super-admin'" class="flex items-center justify-end gap-2">
                                            <button
                                                v-if="isCustomerDirectory"
                                                @click="openCustomerDetails(user)"
                                                class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-50 text-slate-500 transition-all hover:bg-indigo-50 hover:text-indigo-600 dark:bg-slate-800 dark:hover:bg-indigo-900/20"
                                            >
                                                <Eye :size="14" />
                                            </button>
                                            <button
                                                v-if="isCustomerDirectory"
                                                type="button"
                                                @click="openAssignPlanModal(user)"
                                                class="inline-flex h-8 items-center gap-1 rounded-xl bg-primary-50 px-2.5 text-[10px] font-black tracking-wide text-primary-700 uppercase transition-all hover:bg-primary-100 dark:bg-primary-500/15 dark:text-primary-300 dark:hover:bg-primary-500/25"
                                            >
                                                <PlusCircle :size="12" />
                                                {{ __('Assign Plan') }}
                                            </button>
                                            <Link
                                                :href="isCustomerDirectory ? admin.customers.edit.url(user.id) : admin.staff.edit.url(user.id)"
                                                class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-50 text-slate-500 transition-all hover:bg-brand-50 hover:text-brand-600 dark:bg-slate-800 dark:hover:bg-brand-900/20"
                                            >
                                                <Pencil :size="14" />
                                            </Link>
                                            <button
                                                @click="confirmDelete(user)"
                                                class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-50 text-slate-500 transition-all hover:bg-rose-50 hover:text-rose-500 dark:bg-slate-800 dark:hover:bg-rose-900/20"
                                            >
                                                <Trash2 :size="14" />
                                            </button>
                                        </div>
                                        <div v-else class="flex items-center justify-end gap-2">
                                            <span class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">{{ __('Immutable') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td :colspan="tableColumnCount" class="px-6 py-16">
                                    <div class="mx-auto max-w-md text-center">
                                        <p class="text-base font-bold text-slate-800 dark:text-slate-100">
                                            {{
                                                search
                                                    ? isCustomerDirectory
                                                        ? __('No customers match your search.')
                                                        : __('No staff match your search.')
                                                    : isCustomerDirectory
                                                      ? __('No customers found yet.')
                                                      : __('No staff found yet.')
                                            }}
                                        </p>
                                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                            {{
                                                isCustomerDirectory
                                                    ? __('Customer records will appear here once available.')
                                                    : __('Staff accounts will appear here once available.')
                                            }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="hasUsers && users.links.length > 3" class="flex justify-end border-t border-slate-100 p-6 dark:border-slate-700">
                    <!-- Simple pagination implementation -->
                    <div class="flex space-x-1">
                        <Link
                            v-for="(link, key) in users.links"
                            :key="key"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="rounded-lg border border-transparent px-3 py-1 text-xs transition-colors"
                            :class="
                                link.active
                                    ? 'bg-brand-600 text-white'
                                    : 'text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700'
                            "
                            :preserve-state="true"
                        />
                    </div>
                </div>
            </div>

            <ConfirmationModal
                :show="showDeleteModal"
                :title="__('Delete Staff?')"
                :message="__('Are you sure you want to delete this staff? This action cannot be undone.')"
                :confirmText="__('Yes, Delete Staff')"
                :cancelText="__('No, Cancel')"
                type="danger"
                :processing="processingDelete"
                @close="showDeleteModal = false"
                @confirm="handleDelete"
            />

            <Teleport to="body">
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-3 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="translate-y-2 opacity-0"
                >
                    <div v-if="showAssignPlanModal && customerForPlanAssignment" class="fixed inset-0 z-[160] overflow-y-auto p-4 sm:p-6">
                        <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" @click="closeAssignPlanModal"></div>

                        <div class="relative flex min-h-full items-center justify-center">
                            <div
                                class="relative flex max-h-[calc(100vh-2rem)] w-full max-w-xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl sm:max-h-[90vh] dark:border-slate-700 dark:bg-slate-900"
                            >
                                <div
                                    class="sticky top-0 z-10 flex shrink-0 items-start justify-between border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-6 py-5 dark:border-slate-700 dark:from-slate-900 dark:to-slate-900"
                                >
                                    <div>
                                        <p class="text-xs font-black tracking-widest text-brand-600 uppercase dark:text-brand-300">
                                            {{ isRenewingSelectedPlan ? __('Renew Plan') : __('Assign Plan') }}
                                        </p>
                                        <h3 class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ customerForPlanAssignment.name }}</h3>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ customerForPlanAssignment.email }}</p>
                                    </div>
                                    <button
                                        type="button"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                                        @click="closeAssignPlanModal"
                                    >
                                        <X :size="18" />
                                    </button>
                                </div>

                                <form @submit.prevent="submitAssignPlan" class="flex min-h-0 flex-1 flex-col">
                                    <div class="custom-scrollbar min-h-0 flex-1 space-y-5 overflow-y-auto p-6">
                                        <div>
                                            <label class="mb-2 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{
                                                __('Plan')
                                            }}</label>
                                            <select
                                                v-model="assignPlanForm.plan_id"
                                                class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-800 transition-colors focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                            >
                                                <option value="">{{ __('Select a plan') }}</option>
                                                <option v-for="plan in plans" :key="plan.id" :value="`${plan.id}`">
                                                    {{ `${plan.name} (${plan.duration_days} ${__('Days')})` }}
                                                </option>
                                            </select>
                                            <p v-if="assignPlanForm.errors.plan_id" class="mt-2 text-xs font-medium text-red-500">
                                                {{ assignPlanForm.errors.plan_id }}
                                            </p>
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
                                                    v-model="assignPlanForm.start_date"
                                                    type="date"
                                                    class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-800 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                                />
                                                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                                                    {{
                                                        isStartDateInFuture
                                                            ? __('Membership access will remain pending until the selected start date.')
                                                            : __('The membership cycle and renewal dates will be based on this date.')
                                                    }}
                                                </p>
                                                <p v-if="assignPlanForm.errors.start_date" class="mt-1.5 text-xs font-medium text-red-500">
                                                    {{ assignPlanForm.errors.start_date }}
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
                                                    class="inline-flex h-10 items-center justify-center rounded-xl border text-sm font-semibold transition-colors"
                                                    :class="
                                                        assignPlanForm.status === 'active'
                                                            ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                                                            : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'
                                                    "
                                                    @click="assignPlanForm.status = 'active'"
                                                >
                                                    {{ __('Active') }}
                                                </button>
                                                <button
                                                    type="button"
                                                    class="inline-flex h-10 items-center justify-center rounded-xl border text-sm font-semibold transition-colors"
                                                    :class="
                                                        assignPlanForm.status === 'pending'
                                                            ? 'border-amber-500 bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300'
                                                            : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'
                                                    "
                                                    @click="assignPlanForm.status = 'pending'"
                                                >
                                                    {{ __('Pending') }}
                                                </button>
                                            </div>
                                        </div>

                                        <div
                                            v-if="selectedPlanForAssignment"
                                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60"
                                        >
                                            <p class="mb-3 text-[11px] font-black tracking-widest text-slate-500 uppercase dark:text-slate-400">
                                                {{ __('Plan Details') }}
                                            </p>

                                            <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/70">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <h4 class="text-2xl font-extrabold text-slate-900 dark:text-white">
                                                        {{ selectedPlanForAssignment.name }}
                                                    </h4>
                                                    <span
                                                        class="inline-flex items-center rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-[11px] font-bold tracking-widest text-brand-700 uppercase dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300"
                                                    >
                                                        {{ selectedPlanForAssignment.duration_days }} {{ __('Days') }}
                                                    </span>
                                                </div>

                                                <p class="mt-2 text-base font-bold text-brand-600 dark:text-brand-300">
                                                    {{ formatCurrency(selectedPlanForAssignment.price) }}
                                                </p>

                                                <p
                                                    v-if="selectedPlanForAssignment.description"
                                                    class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300"
                                                >
                                                    {{ selectedPlanForAssignment.description }}
                                                </p>

                                                <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                    <div
                                                        class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-800"
                                                    >
                                                        <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                                                            {{ __('Access Type') }}
                                                        </p>
                                                        <p class="mt-1 text-sm font-semibold text-slate-800 capitalize dark:text-slate-200">
                                                            {{ selectedPlanForAssignment.access_type || __('Anytime') }}
                                                        </p>
                                                    </div>
                                                    <div
                                                        class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-800"
                                                    >
                                                        <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                                                            {{ __('Access Window') }}
                                                        </p>
                                                        <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-200">
                                                            {{
                                                                formatTimeWindow(
                                                                    selectedPlanForAssignment.start_time,
                                                                    selectedPlanForAssignment.end_time,
                                                                )
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="mt-4">
                                                    <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                                                        {{ __('Facilities') }}
                                                    </p>
                                                    <div v-if="selectedPlanFacilities.length" class="mt-2 flex flex-wrap gap-2">
                                                        <span
                                                            v-for="facility in selectedPlanFacilities"
                                                            :key="facility"
                                                            class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
                                                        >
                                                            {{ facility }}
                                                        </span>
                                                    </div>
                                                    <p v-else class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                        {{ __('No facilities assigned to this plan.') }}
                                                    </p>
                                                </div>

                                                <div
                                                    v-if="selectedPlanForAssignment.is_offer && selectedPlanForAssignment.offer_price"
                                                    class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 dark:border-amber-500/30 dark:bg-amber-500/10"
                                                >
                                                    <p class="text-[10px] font-bold tracking-widest text-amber-700 uppercase dark:text-amber-300">
                                                        {{ __('Offer Price') }}
                                                    </p>
                                                    <p class="mt-1 text-sm font-bold text-amber-700 dark:text-amber-300">
                                                        {{ formatCurrency(selectedPlanForAssignment.offer_price) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            v-if="!plans.length"
                                            class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300"
                                        >
                                            {{ __('No active plans available. Create or activate a plan first.') }}
                                        </div>
                                    </div>

                                    <div
                                        class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-700"
                                    >
                                        <button
                                            type="button"
                                            class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                            @click="closeAssignPlanModal"
                                        >
                                            {{ __('Cancel') }}
                                        </button>
                                        <button
                                            type="submit"
                                            class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="assignPlanForm.processing || !assignPlanForm.plan_id || !plans.length"
                                        >
                                            {{
                                                assignPlanForm.processing
                                                    ? isRenewingSelectedPlan
                                                        ? __('Renewing...')
                                                        : __('Assigning...')
                                                    : isRenewingSelectedPlan
                                                      ? __('Renew Plan')
                                                      : __('Assign Plan')
                                            }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </Transition>
            </Teleport>

            <Teleport to="body">
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-3 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="translate-y-2 opacity-0"
                >
                    <div v-if="showCustomerDetailsModal && selectedCustomer" class="fixed inset-0 z-[160] overflow-y-auto p-4 sm:p-6">
                        <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" @click="closeCustomerDetails"></div>

                        <div class="relative flex min-h-full items-center justify-center">
                            <div
                                class="relative flex max-h-[calc(100vh-2rem)] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl sm:max-h-[90vh] dark:border-slate-700 dark:bg-slate-900"
                            >
                                <div
                                    class="sticky top-0 z-10 flex shrink-0 items-start justify-between border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-6 py-5 dark:border-slate-700 dark:from-slate-900 dark:to-slate-900"
                                >
                                    <div>
                                        <p class="text-xs font-black tracking-widest text-brand-600 uppercase dark:text-brand-300">
                                            {{ __('Customer Details') }}
                                        </p>
                                        <h3 class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ selectedCustomer.name }}</h3>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ selectedCustomer.email }}</p>
                                    </div>
                                    <button
                                        type="button"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                                        @click="closeCustomerDetails"
                                    >
                                        <X :size="18" />
                                    </button>
                                </div>

                                <div class="custom-scrollbar min-h-0 flex-1 space-y-5 overflow-y-auto p-6">
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
                                            <p class="text-[11px] font-bold tracking-widest text-slate-400 uppercase">{{ __('Phone') }}</p>
                                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
                                                {{
                                                    selectedCustomer.customer_profile?.phone ||
                                                    selectedCustomer.customerProfile?.phone ||
                                                    __('No phone')
                                                }}
                                            </p>
                                        </div>
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
                                            <p class="text-[11px] font-bold tracking-widest text-slate-400 uppercase">{{ __('Address') }}</p>
                                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
                                                {{
                                                    selectedCustomer.customer_profile?.address ||
                                                    selectedCustomer.customerProfile?.address ||
                                                    __('No address')
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="mb-2 text-[11px] font-bold tracking-widest text-slate-400 uppercase">{{ __('Plans') }}</p>
                                        <div v-if="customerPlanSubscriptions.length" class="max-h-72 space-y-2 overflow-y-auto pr-1">
                                            <div
                                                v-for="subscription in customerPlanSubscriptions"
                                                :key="subscription.id"
                                                class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800/60"
                                            >
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <p class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                                        {{ subscription.plan?.name ?? __('Unknown Plan') }}
                                                    </p>
                                                    <span
                                                        class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-bold tracking-widest uppercase"
                                                        :class="getStatusBadgeClass(subscription.status)"
                                                    >
                                                        {{ subscription.status }}
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                    {{ __('Access') }}: {{ subscription.plan?.access_type ?? __('Anytime') }}
                                                </p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                                    {{ __('Starts') }}: {{ formatDate(subscription.start_date) }} • {{ __('Expires') }}:
                                                    {{ formatDate(subscription.end_date) }}
                                                </p>
                                            </div>
                                        </div>
                                        <div
                                            v-else
                                            class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-400"
                                        >
                                            {{ __('No plans assigned yet.') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex shrink-0 justify-end gap-3 border-t border-slate-200 p-6 dark:border-slate-700">
                                    <button
                                        type="button"
                                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                        @click="closeCustomerDetails"
                                    >
                                        {{ __('Close') }}
                                    </button>
                                    <Link
                                        :href="admin.customers.edit.url(selectedCustomer.id)"
                                        class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-700"
                                    >
                                        {{ __('Edit Customer') }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </Teleport>
        </div>
    </AdminLayout>
</template>
