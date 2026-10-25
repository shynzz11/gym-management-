<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Bell, Check, CheckCheck, Trash2, X, Info, CheckCircle, AlertTriangle, AlertCircle } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps<{
    recentUrl: string;
    viewAllUrl: string;
    markAsReadUrl: (id: number) => string;
    markAllAsReadUrl: string;
    deleteUrl: (id: number) => string;
}>();

interface Notification {
    id: number;
    type: 'info' | 'success' | 'warning' | 'error';
    title: string;
    description: string | null;
    action_url: string | null;
    read_at: string | null;
    created_at: string;
}

import { usePage } from '@inertiajs/vue3';

const page = usePage();
const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const isOpen = ref(false);
const isLoading = ref(false);

// Update unread count from Inertia shared data
onMounted(() => {
    unreadCount.value = page.props.auth ? (page.props.auth as any).unread_count : 0;
});

// Fetch notifications
const fetchNotifications = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get(props.recentUrl);
        notifications.value = response.data.notifications;
        unreadCount.value = response.data.unread_count;
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    } finally {
        isLoading.value = false;
    }
};

// Mark as read
const markAsRead = async (id: number) => {
    try {
        await axios.post(props.markAsReadUrl(id));
        const notification = notifications.value.find((n) => n.id === id);
        if (notification) {
            notification.read_at = new Date().toISOString();
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
};

// Mark all as read
const markAllAsRead = async () => {
    try {
        await axios.post(props.markAllAsReadUrl);
        notifications.value.forEach((n) => {
            n.read_at = new Date().toISOString();
        });
        unreadCount.value = 0;
    } catch (error) {
        console.error('Failed to mark all as read:', error);
    }
};

// Delete notification
const deleteNotification = async (id: number) => {
    try {
        await axios.delete(props.deleteUrl(id));
        const index = notifications.value.findIndex((n) => n.id === id);
        if (index !== -1) {
            const wasUnread = !notifications.value[index].read_at;
            notifications.value.splice(index, 1);
            if (wasUnread) {
                unreadCount.value = Math.max(0, unreadCount.value - 1);
            }
        }
    } catch (error) {
        console.error('Failed to delete notification:', error);
    }
};

// Get icon for notification type
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

// Get color classes for notification type
const getTypeClasses = (type: string) => {
    switch (type) {
        case 'success':
            return 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20';
        case 'warning':
            return 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20';
        case 'error':
            return 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20';
        default:
            return 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20';
    }
};

// Format time ago
const timeAgo = (date: string) => {
    const now = new Date();
    const past = new Date(date);
    const seconds = Math.floor((now.getTime() - past.getTime()) / 1000);

    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`;
    return past.toLocaleDateString();
};

// Toggle dropdown
const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value && notifications.value.length === 0) {
        fetchNotifications();
    }
};

// Close dropdown when clicking outside
const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.notification-dropdown')) {
        isOpen.value = false;
    }
};

onMounted(() => {
    fetchNotifications();
    document.addEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="notification-dropdown relative">
        <!-- Bell Button -->
        <button
            @click="toggleDropdown"
            class="relative rounded-lg p-1.5 text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
            :class="{ 'bg-gray-100 dark:bg-gray-700': isOpen }"
        >
            <Bell :size="20" />

            <!-- Unread Badge -->
            <span
                v-if="unreadCount > 0"
                class="absolute top-0 right-0 flex h-[16px] min-w-[16px] items-center justify-center rounded-full bg-red-500 px-0.5 text-[9px] font-bold text-white"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="fixed top-20 right-4 left-4 z-50 w-auto overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl ring-1 ring-black/5 sm:absolute sm:top-auto sm:right-0 sm:left-auto sm:mt-2 sm:w-96 dark:border-slate-700 dark:bg-slate-900"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/50"
                >
                    <h3 class="flex items-center gap-2 font-bold text-gray-900 dark:text-white">
                        <Bell :size="18" class="text-primary-600" />
                        Notifications
                    </h3>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllAsRead"
                        class="flex items-center gap-1 rounded-lg bg-primary-50 px-2 py-1 text-xs font-bold text-primary-600 transition-colors hover:text-primary-700 dark:bg-primary-900/30 dark:text-primary-400 dark:hover:text-primary-300"
                    >
                        <CheckCheck :size="14" />
                        Mark all read
                    </button>
                </div>

                <!-- Notifications List -->
                <div class="custom-scrollbar max-h-[420px] overflow-y-auto">
                    <div v-if="isLoading" class="flex items-center justify-center py-16">
                        <div class="relative flex items-center justify-center">
                            <div class="h-10 w-10 rounded-full border-4 border-primary-100 dark:border-primary-900/30"></div>
                            <div class="absolute h-10 w-10 animate-spin rounded-full border-4 border-t-primary-600"></div>
                        </div>
                    </div>

                    <div v-else-if="notifications.length === 0" class="flex flex-col items-center justify-center px-6 py-16 text-center">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                            <Bell :size="32" class="text-gray-300 dark:text-gray-600" />
                        </div>
                        <h4 class="font-semibold text-gray-900 dark:text-white">No new alerts</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">We'll let you know when something important happens.</p>
                    </div>

                    <div v-else class="divide-y divide-gray-100 dark:divide-gray-800/50">
                        <div
                            v-for="notification in notifications"
                            :key="notification.id"
                            class="group px-5 py-4 transition-all duration-200 hover:bg-slate-50 dark:hover:bg-slate-800/50"
                            :class="{ 'bg-primary-50/50 dark:bg-primary-900/10': !notification.read_at }"
                        >
                            <div class="flex gap-4">
                                <!-- Icon -->
                                <div class="flex-shrink-0">
                                    <div
                                        class="flex h-11 w-11 items-center justify-center rounded-xl shadow-sm transition-transform group-hover:scale-105"
                                        :class="getTypeClasses(notification.type)"
                                    >
                                        <component :is="getTypeIcon(notification.type)" :size="22" />
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="text-sm leading-tight font-bold text-gray-900 dark:text-white">
                                            {{ notification.title }}
                                        </h4>
                                        <div class="flex flex-shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                            <button
                                                v-if="!notification.read_at"
                                                @click="markAsRead(notification.id)"
                                                class="rounded-lg p-1.5 text-gray-400 transition-all hover:bg-primary-50 hover:text-primary-600 dark:hover:bg-primary-900/50 dark:hover:text-primary-400"
                                                title="Mark as read"
                                            >
                                                <Check :size="14" />
                                            </button>
                                            <button
                                                @click="deleteNotification(notification.id)"
                                                class="rounded-lg p-1.5 text-gray-400 transition-all hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/50 dark:hover:text-red-400"
                                                title="Delete"
                                            >
                                                <Trash2 :size="14" />
                                            </button>
                                        </div>
                                    </div>

                                    <p
                                        v-if="notification.description"
                                        class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-gray-600 dark:text-gray-400"
                                    >
                                        {{ notification.description }}
                                    </p>

                                    <div class="mt-3 flex items-center justify-between">
                                        <span class="text-[10px] font-medium tracking-wider text-gray-400 uppercase dark:text-gray-500">
                                            {{ timeAgo(notification.created_at) }}
                                        </span>

                                        <div class="flex items-center gap-2">
                                            <Link
                                                v-if="notification.type === 'warning' || notification.title.toLowerCase().includes('expire')"
                                                :href="notification.action_url || '/customer/dashboard'"
                                                class="transform rounded-lg bg-orange-600 px-3 py-1 text-xs font-bold text-white shadow-md shadow-orange-500/20 transition-all hover:-translate-y-0.5 hover:bg-orange-700"
                                                @click="isOpen = false"
                                            >
                                                Renew Now
                                            </Link>
                                            <Link
                                                v-else-if="notification.action_url"
                                                :href="notification.action_url"
                                                class="text-xs font-bold text-primary-600 hover:underline dark:text-primary-400"
                                                @click="isOpen = false"
                                            >
                                                View Details
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    v-if="notifications.length > 0"
                    class="border-t border-slate-100 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/50"
                >
                    <Link
                        :href="viewAllUrl"
                        class="block text-center text-sm font-bold text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                        @click="isOpen = false"
                    >
                        See All Notifications
                    </Link>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}
</style>
