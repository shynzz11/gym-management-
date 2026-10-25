<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight, ArrowLeft, Clock, Monitor, Globe, User as UserIcon, FileText, History } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import admin from '@/routes/admin';

defineProps<{
    log: any;
}>();

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
    return new Date(dateString).toLocaleString();
};

const formatJson = (json: any) => {
    return JSON.stringify(json, null, 2);
};
</script>

<template>
    <Head :title="__('Activity Details')" />

    <AdminLayout>
        <div class="animate-fade-in mx-auto w-full max-w-4xl space-y-6 p-4 lg:p-0">
            <!-- Header section -->
            <div class="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
                <div>
                    <div class="mb-2 flex items-center gap-2 text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        <Link :href="admin.activity_logs.index.url()" class="hover:text-brand-500">{{ __('Activity Log') }}</Link>
                        <ChevronRight :size="8" class="opacity-40 rtl:rotate-180" />
                        <span class="text-brand-500">{{ __('Details') }}</span>
                    </div>
                    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ __('Log Details') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Deep audit trail analysis for event') }} #{{ log.id }}</p>
                </div>
                <Link
                    :href="admin.activity_logs.index.url()"
                    class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3 text-sm font-bold text-slate-600 shadow-sm transition-all hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                >
                    <ArrowLeft :size="16" class="rtl:rotate-180" />
                    {{ __('Back to List') }}
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Left Sidebar: Metadata -->
                <div class="space-y-6">
                    <div class="space-y-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                                <UserIcon :size="24" />
                            </div>
                            <div>
                                <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">{{ __('Subject') }}</p>
                                <h4 class="text-lg font-bold text-slate-900 dark:text-white">{{ log.user ? log.user.name : 'System' }}</h4>
                                <p class="text-xs text-slate-400">{{ log.user ? log.user.email : 'Automated Task' }}</p>
                            </div>
                        </div>

                        <div class="space-y-4 border-t border-slate-100 pt-6 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <Clock :size="16" class="text-slate-400" />
                                <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                    <span class="block font-bold text-slate-900 dark:text-white">{{ formatDate(log.created_at) }}</span>
                                    {{ __('Timestamp') }}
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <Globe :size="16" class="text-slate-400" />
                                <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                    <span class="block font-bold text-slate-900 dark:text-white">{{ log.ip_address }}</span>
                                    IP Address
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <Monitor :size="16" class="text-slate-400" />
                                <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                    <span class="block max-w-[180px] truncate font-bold text-slate-900 dark:text-white" :title="log.user_agent">{{
                                        log.user_agent
                                    }}</span>
                                    User Agent
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-6 dark:border-slate-800">
                            <span
                                class="inline-block w-full rounded-full px-4 py-1.5 text-center text-xs font-bold tracking-wider uppercase"
                                :class="getEventBadgeClass(log.event)"
                            >
                                {{ log.event.replace('_', ' ') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right Content: Description & Properties -->
                <div class="space-y-6 lg:col-span-2">
                    <div class="space-y-8 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div>
                            <div class="mb-4 flex items-center gap-2">
                                <FileText :size="18" class="text-brand-500" />
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Activity Summary') }}</h3>
                            </div>
                            <p
                                class="rounded-2xl border border-slate-100 bg-slate-50 p-6 font-medium text-slate-600 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-300"
                            >
                                {{ log.description }}
                            </p>
                        </div>

                        <div v-if="log.properties">
                            <div class="mb-4 flex items-center gap-2">
                                <History :size="18" class="text-brand-500" />
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Data Changes') }}</h3>
                            </div>

                            <div
                                class="overflow-hidden overflow-x-auto rounded-2xl border border-slate-100 bg-slate-900 p-6 font-mono text-xs text-slate-300 shadow-inner dark:border-slate-800"
                            >
                                <pre>{{ formatJson(log.properties) }}</pre>
                            </div>
                        </div>

                        <div v-if="log.subject_type">
                            <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">{{ __('Subject Information') }}</p>
                            <div class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-400">
                                {{ __('Type') }}: <span class="text-slate-900 dark:text-white">{{ log.subject_type }}</span
                                ><br />
                                {{ __('ID') }}: <span class="text-slate-900 dark:text-white">{{ log.subject_id }}</span>
                            </div>
                        </div>
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
