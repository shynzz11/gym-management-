<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Bell, Check, Trash2, Info, CheckCircle, AlertTriangle, AlertCircle, Clock } from 'lucide-vue-next';
import axios from 'axios';
import { read, destroy } from '@/routes/customer/notifications';

interface Notification {
    id: string | number;
    type: 'info' | 'success' | 'warning' | 'error';
    title: string;
    description: string | null;
    action_url: string | null;
    read_at: string | null;
    created_at: string;
}

const props = defineProps<{
    notifications: Notification[];
}>();

const notificationsList = ref([...props.notifications]);

const markAsRead = async (id: string | number) => {
    try {
        await axios.post(read.url({ id }));
        const notification = notificationsList.value.find((n) => n.id === id);
        if (notification) {
            notification.read_at = new Date().toISOString();
        }
    } catch (error) {
        console.error('Failed to mark as read', error);
    }
};

const deleteNotification = async (id: string | number) => {
    try {
        await axios.delete(destroy.url({ id }));
        notificationsList.value = notificationsList.value.filter((n) => n.id !== id);
    } catch (error) {
        console.error('Failed to delete notification', error);
    }
};

const getTypeIcon = (type: string) => {
    switch (type) {
        case 'success':
            return CheckCircle;
        case 'warning':
            return AlertTriangle;
        case 'error':
            return AlertCircle;
        default:
            return Info;
    }
};

const getTypeClasses = (type: string) => {
    switch (type) {
        case 'success':
            return 'text-green-600 bg-green-50 dark:bg-green-900/20';
        case 'warning':
            return 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20';
        case 'error':
            return 'text-red-600 bg-red-50 dark:bg-red-900/20';
        default:
            return 'text-sky-600 bg-sky-50 dark:bg-sky-900/20';
    }
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Notifications" />

    <CustomerLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="flex items-center gap-3 text-2xl font-bold text-slate-900 dark:text-white">
                        <Bell class="h-7 w-7 text-sky-600" />
                        {{ __('Notifications') }}
                    </h1>
                    <p class="mt-1 text-slate-500 dark:text-slate-400">
                        {{ __('Stay updated with your membership status and alerts.') }}
                    </p>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <div v-if="notificationsList.length === 0" class="flex flex-col items-center justify-center px-4 py-20 text-center">
                    <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800/50">
                        <Bell class="h-10 w-10 text-slate-300 dark:text-slate-700" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('All caught up!') }}</h3>
                    <p class="mt-1 max-w-xs text-slate-500 dark:text-slate-400">
                        {{ __("You don't have any notifications at the moment. We'll alert you here when something important happens.") }}
                    </p>
                </div>

                <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                    <div
                        v-for="notification in notificationsList"
                        :key="notification.id"
                        class="p-4 transition-colors duration-200 lg:p-6"
                        :class="[notification.read_at ? 'bg-white dark:bg-slate-900' : 'bg-sky-50/30 dark:bg-sky-900/10']"
                    >
                        <div class="flex gap-4">
                            <!-- Icon -->
                            <div class="flex-shrink-0">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl" :class="getTypeClasses(notification.type)">
                                    <component :is="getTypeIcon(notification.type)" class="h-6 w-6" />
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="font-bold text-slate-900 dark:text-white" :class="{ 'opacity-70': notification.read_at }">
                                            {{ notification.title }}
                                        </h3>
                                        <p class="mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                            {{ notification.description }}
                                        </p>
                                    </div>
                                    <div class="flex flex-shrink-0 items-center gap-2">
                                        <button
                                            v-if="!notification.read_at"
                                            @click="markAsRead(notification.id)"
                                            class="rounded-xl p-2 text-slate-400 transition-all hover:bg-sky-50 hover:text-sky-600 dark:hover:bg-sky-900/30"
                                            title="Mark as read"
                                        >
                                            <Check class="h-4 w-4" />
                                        </button>
                                        <button
                                            @click="deleteNotification(notification.id)"
                                            class="rounded-xl p-2 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/30"
                                            title="Delete notification"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between">
                                    <div class="flex items-center gap-2 text-xs text-slate-400">
                                        <Clock class="h-3.5 w-3.5" />
                                        {{ formatDate(notification.created_at) }}
                                    </div>
                                    <Component
                                        :is="notification.action_url ? Link : 'div'"
                                        v-if="notification.action_url"
                                        :href="notification.action_url"
                                        class="rounded-xl bg-slate-100 px-4 py-1.5 text-xs font-bold text-slate-700 transition-all hover:bg-sky-600 hover:text-white dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-sky-600"
                                    >
                                        {{ __('View Details') }}
                                    </Component>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
