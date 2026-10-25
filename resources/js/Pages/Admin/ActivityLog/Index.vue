<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Gauge, Search, ChevronRight, ChevronLeft, Bolt, Bot, User as UserIcon, Info, Download } from 'lucide-vue-next';
import { debounce } from 'lodash';
import admin from '@/routes/admin';

const props = defineProps<{
    logs: {
        data: any[];
        links: any[];
        total: number;
        from: number;
        to: number;
    };
    stats: {
        total_events: number;
        today_events: number;
        security_events: number;
        critical_alerts: number;
    };
    filters: {
        search?: string;
        event?: string;
        date_range?: string;
    };
}>();

const search = ref(props.filters.search || '');
const event = ref(props.filters.event || '');
const date_range = ref(props.filters.date_range || '');

const debouncedSearch = debounce(() => {
    router.get(
        admin.activity_logs.index.url(),
        {
            search: search.value,
            event: event.value,
            date_range: date_range.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}, 300);

watch(search, debouncedSearch);

const applyFilters = () => {
    router.get(
        admin.activity_logs.index.url(),
        {
            search: search.value,
            event: event.value,
            date_range: date_range.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const getEventBadgeClass = (type: string) => {
    switch (type) {
        case 'created':
            return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600';
        case 'updated':
            return 'bg-sky-100 dark:bg-sky-900/30 text-sky-600';
        case 'deleted':
            return 'bg-rose-100 dark:bg-rose-900/30 text-rose-600';
        case 'failed_login':
            return 'bg-amber-100 dark:bg-amber-900/30 text-amber-600';
        default:
            return 'bg-slate-100 dark:bg-slate-800 text-slate-600';
    }
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const getRelativeTime = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now.getTime() - date.getTime();

    if (diff < 60000) return 'Just now';
    if (diff < 3600000) return Math.floor(diff / 60000) + ' mins ago';
    if (diff < 86400000) return Math.floor(diff / 3600000) + ' hours ago';
    return date.toLocaleDateString();
};

const exportCsv = () => {
    window.location.href = admin.activity_logs.index.url({ query: { ...props.filters, export: 'csv' } });
};
</script>

<template>
    <Head :title="__('Activity Log')" />

    <AdminLayout>
        <div class="animate-fade-in mx-auto w-full max-w-7xl space-y-6 p-4 lg:p-0">
            <!-- Header section -->
            <div class="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ __('Activity Log') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Real-time infrastructure audit trail') }}</p>
                </div>
                <button
                    @click="exportCsv"
                    class="flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-xl transition-all hover:opacity-90 dark:bg-white dark:text-slate-900"
                >
                    <Download :size="16" />
                    {{ __('Export CSV') }}
                </button>
            </div>

            <!-- Stats section -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div
                    class="flex items-center gap-5 rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-600 dark:bg-sky-900/30">
                        <Bolt :size="24" />
                    </div>
                    <div>
                        <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">{{ __('Total Events') }}</p>
                        <h4 class="text-xl font-black text-slate-900 dark:text-white">{{ stats.total_events }}</h4>
                    </div>
                </div>
                <div
                    class="flex items-center gap-5 rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30">
                        <Gauge :size="24" />
                    </div>
                    <div>
                        <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">{{ __('Today Events') }}</p>
                        <h4 class="text-xl font-black text-slate-900 dark:text-white">{{ stats.today_events }}</h4>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-4 lg:flex-row">
                    <div class="relative flex-1">
                        <Search :size="18" class="absolute start-5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="__('Search activity, users or specific IP addresses...')"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 ps-12 font-medium text-slate-900 transition-all outline-none focus:ring-2 focus:ring-sky-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <select
                            v-model="event"
                            @change="applyFilters"
                            class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-900 transition-all outline-none focus:ring-2 focus:ring-sky-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        >
                            <option value="">{{ __('All Types') }}</option>
                            <option value="created">Created</option>
                            <option value="updated">Updated</option>
                            <option value="deleted">Deleted</option>
                            <option value="failed_login">Security</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table section -->
            <div class="overflow-hidden rounded-[2.5rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-800/50">
                                <th class="px-6 py-3 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase">
                                    {{ __('User / Subject') }}
                                </th>
                                <th class="px-6 py-3 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase">
                                    {{ __('Activity Description') }}
                                </th>
                                <th class="px-6 py-3 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase">
                                    {{ __('Status') }}
                                </th>
                                <th class="px-6 py-3 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase">{{ __('Time') }}</th>
                                <th class="px-6 py-3 text-center text-[10px] font-bold tracking-widest text-slate-400 uppercase">
                                    {{ __('Details') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="log in logs.data" :key="log.id" class="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-slate-800"
                                        >
                                            <component :is="log.user ? UserIcon : Bot" :size="20" />
                                        </div>
                                        <div class="text-start">
                                            <p class="text-sm leading-tight font-bold text-slate-900 dark:text-white">
                                                {{ log.user ? log.user.name : 'System / Bot' }}
                                            </p>
                                            <p class="text-[10px] font-medium tracking-tight text-slate-400">
                                                {{ log.ip_address }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    <p class="max-w-md text-sm font-medium text-slate-600 dark:text-slate-300">
                                        {{ log.description }}
                                    </p>
                                </td>
                                <td class="px-6 py-3">
                                    <span
                                        class="rounded-full px-3 py-1 text-[10px] font-bold tracking-wider uppercase"
                                        :class="getEventBadgeClass(log.event)"
                                    >
                                        {{ log.event.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <p class="text-xs font-bold text-slate-900 dark:text-white">{{ getRelativeTime(log.created_at) }}</p>
                                    <p class="text-[10px] font-medium text-slate-400">{{ formatDate(log.created_at) }}</p>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <Link
                                        :href="admin.activity_logs.show.url(log.id)"
                                        class="mx-auto flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-400 transition-all hover:text-sky-500 dark:bg-slate-800"
                                    >
                                        <ChevronRight :size="14" class="rtl:rotate-180" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="5" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <Info :size="40" class="text-slate-200 dark:text-slate-700" />
                                        <p class="font-medium text-slate-500">{{ __('No activity logs found.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Section -->
                <div
                    class="flex flex-col items-center justify-between gap-4 border-t border-slate-100 bg-slate-50/50 p-5 sm:flex-row dark:border-slate-800 dark:bg-slate-800/30"
                >
                    <p class="text-[11px] font-bold tracking-widest text-slate-400 uppercase">
                        {{ __('Showing') }} {{ logs.from || 0 }} {{ __('of') }} {{ logs.total }} {{ __('events') }}
                    </p>
                    <div class="flex items-center gap-2">
                        <template v-for="(link, index) in logs.links" :key="index">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="flex h-10 w-10 items-center justify-center rounded-xl text-sm font-bold shadow-sm transition-all"
                                :class="
                                    link.active
                                        ? 'bg-sky-600 text-white shadow-sky-600/20'
                                        : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700'
                                "
                            >
                                <ChevronLeft v-if="link.label.includes('Previous')" :size="16" />
                                <ChevronRight v-if="link.label.includes('Next')" :size="16" />
                                <span v-if="!link.label.includes('Previous') && !link.label.includes('Next')" v-html="link.label"></span>
                            </Link>
                            <div v-else-if="link.label === '...'" class="flex h-10 w-10 items-center justify-center text-slate-400">...</div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
