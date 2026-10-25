<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { DollarSign, Users, CalendarClock, Activity, UserX, MessageCircle } from 'lucide-vue-next';
import dayjs from 'dayjs';
import useCurrency from '@/Composables/useCurrency';
import admin from '@/routes/admin';
import { computed } from 'vue';

const props = defineProps<{
    stats: {
        total_members: number;
        total_revenue: number;
        expiring_soon: number;
    };
    recent_transactions: Array<{
        id: number;
        amount: string;
        status: string;
        created_at: string;
        subscription?: {
            user_id: number;
            user?: { name?: string };
            plan?: { name?: string };
        };
    }>;
    expired_subscriptions: Array<{
        id: number;
        user_id: number;
        status: string;
        end_date: string;
        user?: { name?: string };
        plan?: { name?: string };
    }>;
    expiring_members: Array<{
        id: number;
        name: string;
        phone: string | null;
        end_date: string;
    }>;
}>();

const { formatCurrency } = useCurrency();
const page = usePage();
const businessSettings = computed(() => (page.props.branding as any)?.business_settings || {});
const businessName = computed(() => businessSettings.value.business_name || page.props.name || 'GymOS');

const formatDateTime = (date: string): string => {
    return dayjs(date).format('MMM D, YYYY h:mm A');
};

const formatDate = (date: string): string => {
    return dayjs(date).format('MMM D, YYYY');
};

const whatsappUrl = (member: (typeof props.expiring_members)[number]): string => {
    const message = `Hello ${member.name},\nYour membership at IRON-NEST is valid until ${formatDate(member.end_date)}. We would love to see you continue your fitness journey with us! You can easily pay at the counter next time you drop by.\nPls ignore if already renewed. Thank you!`;

    return `https://wa.me/${member.phone}?text=${encodeURIComponent(message)}`;
};
</script>

<template>
    <Head :title="__('Command Center')" />

    <AdminLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ businessName }} {{ __('Command Center') }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Real-time gym operations and revenue overview.') }}</p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">{{ __('Total Members') }}</p>
                        <Users class="h-4 w-4 text-slate-500" />
                    </div>
                    <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">{{ stats.total_members }}</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">{{ __('Revenue') }}</p>
                        <DollarSign class="h-4 w-4 text-slate-500" />
                    </div>
                    <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">{{ formatCurrency(stats.total_revenue) }}</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">{{ __('Expiring Soon') }}</p>
                        <CalendarClock class="h-4 w-4 text-slate-500" />
                    </div>
                    <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">{{ stats.expiring_soon }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <div class="flex items-center gap-2">
                        <CalendarClock class="h-4 w-4 text-slate-500" />
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Expiring Soon') }}</h2>
                    </div>
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('Next 5 days') }}</span>
                </div>

                <div v-if="expiring_members.length" class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-900/40">
                            <tr>
                                <th class="px-5 py-3 text-xs font-semibold tracking-wider text-slate-500 uppercase">{{ __('Member') }}</th>
                                <th class="px-5 py-3 text-xs font-semibold tracking-wider text-slate-500 uppercase">{{ __('Phone') }}</th>
                                <th class="px-5 py-3 text-xs font-semibold tracking-wider text-slate-500 uppercase">{{ __('Expires') }}</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold tracking-wider text-slate-500 uppercase">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/70">
                            <tr v-for="member in expiring_members" :key="member.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                                <td class="px-5 py-3 font-medium text-slate-700 dark:text-slate-200">{{ member.name }}</td>
                                <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ member.phone ? `+${member.phone}` : __('Phone unavailable') }}</td>
                                <td class="px-5 py-3 font-semibold text-amber-600 dark:text-amber-400">{{ formatDate(member.end_date) }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a
                                        v-if="member.phone"
                                        :href="whatsappUrl(member)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-emerald-700"
                                    >
                                        <MessageCircle class="h-4 w-4" />
                                        {{ __('Notify on WhatsApp') }}
                                    </a>
                                    <span v-else class="text-xs text-slate-400">{{ __('Phone unavailable') }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                    {{ __('No memberships expiring in the next 5 days.') }}
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Recent Transactions Section -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                        <div class="flex items-center gap-2">
                            <Activity class="h-4 w-4 text-slate-500" />
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Recent Transactions') }}</h2>
                        </div>
                        <Link
                            :href="admin.payments.index.url()"
                            class="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300"
                        >
                            {{ __('View All') }}
                        </Link>
                    </div>

                    <div v-if="recent_transactions.length" class="overflow-x-auto text-nowrap">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-900/40">
                                <tr>
                                    <th class="px-5 py-3 text-xs font-semibold tracking-wider text-slate-500 uppercase">{{ __('Member') }}</th>
                                    <th class="px-5 py-3 text-xs font-semibold tracking-wider text-slate-500 uppercase">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/70">
                                <tr v-for="transaction in recent_transactions" :key="transaction.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                                    <td class="px-5 py-3">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ transaction.subscription?.user?.name || '-' }}</span>
                                            <span class="text-xs text-slate-500">{{ transaction.subscription?.plan?.name || '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-slate-900 dark:text-white">{{ formatCurrency(Number(transaction.amount)) }}</span>
                                            <span class="text-[10px] text-slate-400 font-normal uppercase">{{ transaction.status }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                        {{ __('No transactions yet.') }}
                    </div>
                </div>

                <!-- Expired Subscriptions Section -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                        <div class="flex items-center gap-2">
                            <UserX class="h-4 w-4 text-slate-500" />
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Already Expired Subscriptions') }}</h2>
                        </div>
                        <Link
                            :href="admin.subscriptions.index.url()"
                            class="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300"
                        >
                            {{ __('View All') }}
                        </Link>
                    </div>

                    <div v-if="expired_subscriptions.length" class="overflow-x-auto text-nowrap">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-900/40">
                                <tr>
                                    <th class="px-5 py-3 text-xs font-semibold tracking-wider text-slate-500 uppercase">{{ __('Member') }}</th>
                                    <th class="px-5 py-3 text-xs font-semibold tracking-wider text-slate-500 uppercase">{{ __('Expired') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/70">
                                <tr v-for="subscription in expired_subscriptions" :key="subscription.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                                    <td class="px-5 py-3">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ subscription.user?.name || '-' }}</span>
                                            <span class="text-xs text-slate-500">{{ subscription.plan?.name || '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-rose-600 dark:text-rose-400">{{ formatDate(subscription.end_date) }}</span>
                                            <span class="text-[10px] text-slate-400 font-normal uppercase">{{ __('Status') }}: {{ subscription.status }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                        {{ __('No expired subscriptions found.') }}
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
