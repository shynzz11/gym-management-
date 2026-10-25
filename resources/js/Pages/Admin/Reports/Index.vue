<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, PropType, ref } from 'vue';
import { AlertCircle, BarChart2, CalendarClock, RefreshCcw, UserCheck } from 'lucide-vue-next';
import dayjs from 'dayjs';
import useCurrency from '@/Composables/useCurrency';

type ReportTabKey = 'expired' | 'expiringSoon' | 'monthlyRenewals' | 'monthlyAdded';

const props = defineProps({
    expiredMembers: Array as PropType<any[]>,
    expiringSoonMembers: Array as PropType<any[]>,
    monthlyRenewals: Array as PropType<any[]>,
    monthlyAddedPlans: Array as PropType<any[]>,
    meta: Object as PropType<{
        expiringSoonDayLimit: number;
        monthLabel: string;
    }>,
});

const activeTab = ref<ReportTabKey>('expired');
const { formatCurrency } = useCurrency();

const tabs = computed(() => {
    return [
        { key: 'expired' as const, label: 'Expired Members', count: props.expiredMembers?.length ?? 0, icon: AlertCircle },
        {
            key: 'expiringSoon' as const,
            label: `Expiring Soon (${props.meta?.expiringSoonDayLimit ?? 7} Days)`,
            count: props.expiringSoonMembers?.length ?? 0,
            icon: CalendarClock,
        },
        { key: 'monthlyRenewals' as const, label: 'This Month Renew', count: props.monthlyRenewals?.length ?? 0, icon: RefreshCcw },
        { key: 'monthlyAdded' as const, label: 'This Month Added Plans', count: props.monthlyAddedPlans?.length ?? 0, icon: UserCheck },
    ];
});

const formatDate = (value: string | null | undefined, format = 'MMM D, YYYY') => {
    return value ? dayjs(value).format(format) : '-';
};

const formatDateTime = (value: string | null | undefined) => {
    return formatDate(value, 'MMM D, YYYY h:mm A');
};
</script>

<template>
    <Head :title="__('Membership Reports')" />

    <AdminLayout>
        <div class="animate-fade-in space-y-6">
            <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ __('Membership Reports') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Tab-based member and plan reports for expiry, renewals, and monthly additions.') }}
                    </p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-2xl border border-primary-100 bg-primary-50 px-4 py-2 text-sm font-bold text-primary-700">
                    <BarChart2 :size="16" />
                    {{ meta?.monthLabel || __('Current Month') }}
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    @click="activeTab = tab.key"
                    class="rounded-2xl border p-4 text-left transition-all"
                    :class="
                        activeTab === tab.key
                            ? 'border-primary-200 bg-primary-50 shadow-sm dark:border-primary-500/40 dark:bg-primary-500/10'
                            : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800'
                    "
                >
                    <div class="flex items-center justify-between">
                        <component :is="tab.icon" :size="18" class="text-primary-600 dark:text-primary-400" />
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                            {{ tab.count }}
                        </span>
                    </div>
                    <p class="mt-3 text-sm font-bold text-slate-900 dark:text-white">{{ __(tab.label) }}</p>
                </button>
            </div>

            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="overflow-x-auto">
                    <table v-if="activeTab === 'expired'" class="w-full min-w-[900px] text-left">
                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Member') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Plan') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Start Date') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('End Date') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Overdue') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr v-for="item in expiredMembers" :key="item.id">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ item.member_name }}</p>
                                    <p class="text-xs text-slate-500">{{ item.member_email }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ item.plan_name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ formatDate(item.start_date) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ formatDate(item.end_date) }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-red-600">{{ item.days_overdue }} {{ __('days') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-bold text-red-700">
                                        {{ item.status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!expiredMembers?.length">
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">{{ __('No expired members found.') }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <table v-else-if="activeTab === 'expiringSoon'" class="w-full min-w-[900px] text-left">
                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Member') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Plan') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('End Date') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Days Left') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr v-for="item in expiringSoonMembers" :key="item.id">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ item.member_name }}</p>
                                    <p class="text-xs text-slate-500">{{ item.member_email }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ item.plan_name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ formatDate(item.end_date) }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-amber-600">{{ item.days_left }} {{ __('days') }}</td>
                            </tr>
                            <tr v-if="!expiringSoonMembers?.length">
                                <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">
                                    {{ __('No members are expiring soon.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table v-else-if="activeTab === 'monthlyRenewals'" class="w-full min-w-[1000px] text-left">
                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Member') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Plan') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Amount') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Method') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Renewed At') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr v-for="item in monthlyRenewals" :key="item.id">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ item.member_name }}</p>
                                    <p class="text-xs text-slate-500">{{ item.member_email }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ item.plan_name }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-emerald-600">{{ formatCurrency(item.amount) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ item.method || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ item.status || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ formatDateTime(item.paid_at) }}</td>
                            </tr>
                            <tr v-if="!monthlyRenewals?.length">
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">{{ __('No renewals found for this month.') }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <table v-else class="w-full min-w-[1000px] text-left">
                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Member') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Plan') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Start Date') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('End Date') }}</th>
                                <th class="px-4 py-3 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Added At') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr v-for="item in monthlyAddedPlans" :key="item.id">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ item.member_name }}</p>
                                    <p class="text-xs text-slate-500">{{ item.member_email }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ item.plan_name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ item.status || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ formatDate(item.start_date) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ formatDate(item.end_date) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ formatDateTime(item.added_at) }}</td>
                            </tr>
                            <tr v-if="!monthlyAddedPlans?.length">
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                                    {{ __('No member plan additions found for this month.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
