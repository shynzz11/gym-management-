<script setup lang="ts">
import AppTooltip from '@/Components/Common/AppTooltip.vue';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import useCurrency from '@/Composables/useCurrency';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarClock, Wallet } from 'lucide-vue-next';
import dayjs from 'dayjs';

const props = defineProps<{
    payments: {
        data: any[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}>();
const { formatCurrency } = useCurrency();

const formatDate = (date: string): string => {
    return date ? dayjs(date).format('MMM D, YYYY h:mm A') : '-';
};

const formatTransactionType = (value: string | null | undefined): string => {
    if (value === 'renew') {
        return 'Renew';
    }

    return 'New';
};

const transactionTypeClass = (value: string | null | undefined): string => {
    if (value === 'renew') {
        return 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300';
    }

    return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';
};

const normalizeStatus = (value: string | null | undefined): string => {
    return `${value ?? ''}`.trim().toLowerCase();
};

const paymentStatusClass = (value: string | null | undefined): string => {
    const normalizedStatus = normalizeStatus(value);

    if (normalizedStatus === 'completed' || normalizedStatus === 'processed' || normalizedStatus === 'approved') {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
    }

    if (normalizedStatus === 'canceled' || normalizedStatus === 'cancelled' || normalizedStatus === 'denied' || normalizedStatus === 'failed') {
        return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300';
    }

    return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
};

const paymentStatusTooltip = (payment: any): string => {
    const normalizedStatus = normalizeStatus(payment?.status);
    const denialReasonText = String(payment?.denial_reason ?? payment?.cancel_reason ?? payment?.cancellation_reason ?? '').trim();
    const isCanceledStatus =
        normalizedStatus === 'canceled' || normalizedStatus === 'cancelled' || normalizedStatus === 'denied' || normalizedStatus === 'failed';

    if (isCanceledStatus && denialReasonText !== '') {
        return `Cancellation reason: ${denialReasonText}`;
    }

    return '';
};
</script>

<template>
    <Head :title="__('Transactions History')" />

    <CustomerLayout>
        <div class="animate-fade-in relative z-10 mx-auto max-w-6xl space-y-6">
            <div>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ __('Transactions History') }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ __('Review online and cash payment records for your subscriptions.') }}
                </p>
            </div>

            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-800">
                <div class="border-b border-slate-100 p-5 dark:border-slate-700/50">
                    <h3 class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white">
                        <Wallet :size="18" class="text-primary-500" /> {{ __('Payments') }}
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50">
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Plan') }}</th>
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Method') }}</th>
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Type') }}</th>
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Status') }}</th>
                                <th class="p-4 text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Date') }}</th>
                                <th class="p-4 text-right text-xs font-bold tracking-widest text-slate-400 uppercase">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                                <td class="p-4">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ payment.subscription?.plan?.name ?? __('Gym Membership') }}</p>
                                </td>
                                <td class="p-4">
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ payment.method }}</span>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold uppercase" :class="transactionTypeClass(payment.transaction_type)">
                                        {{ formatTransactionType(payment.transaction_type) }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <AppTooltip :text="paymentStatusTooltip(payment)">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold uppercase"
                                            :class="paymentStatusClass(payment.status)"
                                        >
                                            {{ payment.status }}
                                        </span>
                                    </AppTooltip>
                                </td>
                                <td class="p-4 text-sm text-slate-500 dark:text-slate-400">
                                    <span class="inline-flex items-center gap-1">
                                        <CalendarClock :size="14" /> {{ formatDate(payment.created_at) }}
                                    </span>
                                </td>
                                <td class="p-4 text-right text-sm font-black text-slate-900 dark:text-white">{{ formatCurrency(payment.amount) }}</td>
                            </tr>
                            <tr v-if="!payments.data.length">
                                <td colspan="6" class="p-10 text-center text-sm text-slate-500 italic">
                                    {{ __('No transactions found yet.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="payments.links?.length > 3" class="flex justify-end gap-1 border-t border-slate-100 p-5 dark:border-slate-700/50">
                    <Link
                        v-for="(link, index) in payments.links"
                        :key="index"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="rounded-lg border border-transparent px-3 py-1 text-xs transition-colors"
                        :class="
                            link.active
                                ? 'bg-primary-600 text-white'
                                : 'text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700'
                        "
                        :preserve-state="true"
                    />
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
