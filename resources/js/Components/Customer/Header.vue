<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { logout } from '@/routes';
import customer from '@/routes/customer';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import NotificationBell from '@/Components/Common/NotificationBell.vue';

const emit = defineEmits(['toggleSidebar', 'toggleSidebarSize']);
const page = usePage();

const isUserDropdownOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const toggleUserDropdown = () => {
    isUserDropdownOpen.value = !isUserDropdownOpen.value;
};

const closeDropdown = (e: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
        isUserDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closeDropdown);
    // Theme Init -> Respect localStorage, default to light
    if (localStorage.theme === 'dark') {
        isDark.value = true;
    }
});

onUnmounted(() => {
    document.removeEventListener('click', closeDropdown);
});

// Branding helper
const t = (key: string) => {
    if (key === 'title') return page.props.branding?.customer?.header?.title || 'customer_title';
    if (key === 'subtitle') return page.props.branding?.customer?.header?.subtitle || 'customer_subtitle';
    return page.props.branding?.customer?.header?.[key] || key;
};

// Theme Logic
const isDark = ref(false);
const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

const reloadPage = (): void => {
    window.location.reload();
};
</script>

<template>
    <header
        class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-admin-sidebar-border bg-admin-header px-4 backdrop-blur-md transition-colors duration-300 lg:px-6 dark:border-admin-sidebar-border-dark dark:bg-admin-header-dark"
    >
        <div class="flex items-center gap-4">
            <!-- Mobile Sidebar Toggle -->
            <button
                @click="emit('toggleSidebar')"
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition-all hover:bg-slate-200 lg:hidden dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700"
            >
                <svg class="h-5 w-5 rtl:scale-x-[-1]" fill="currentColor" viewBox="0 0 448 512">
                    <path
                        d="M0 96C0 78.3 14.3 64 32 64H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H416c17.7 0 32 14.3 32 32z"
                    />
                </svg>
            </button>

            <!-- Desktop Collapse Toggle -->
            <button
                @click="emit('toggleSidebarSize')"
                class="hidden h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition-all hover:text-sky-600 lg:flex dark:bg-slate-800 dark:text-slate-400"
            >
                <svg class="h-5 w-5 rtl:scale-x-[-1]" fill="currentColor" viewBox="0 0 448 512">
                    <path
                        d="M0 96C0 78.3 14.3 64 32 64H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H416c17.7 0 32 14.3 32 32z"
                    />
                </svg>
            </button>

            <div class="text-start">
                <h1 class="text-sm font-extrabold text-nowrap text-slate-900 md:text-base dark:text-white">{{ __(t('title')) }}</h1>
                <p class="text-[10px] font-medium text-nowrap text-slate-400">{{ __(t('subtitle')) }}</p>
            </div>
        </div>

        <div class="relative flex items-center gap-2 md:gap-4">
            <NotificationBell
                :recent-url="customer.notifications.recent.url()"
                :view-all-url="customer.notifications.index.url()"
                :mark-as-read-url="(id) => customer.notifications.read.url({ id })"
                :mark-all-as-read-url="customer.notifications.read_all.url()"
                :delete-url="(id) => customer.notifications.destroy.url({ id })"
            />

            <div class="hidden md:block">
                <ThemeToggle />
            </div>

            <button
                @click="reloadPage"
                class="hidden items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition-all hover:bg-slate-50 md:flex dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                :title="__('Refresh')"
            >
                <svg class="h-3 w-3 md:me-2 rtl:scale-x-[-1]" fill="currentColor" viewBox="0 0 512 512">
                    <path
                        d="M463.5 224H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1c-87.5 87.5-87.5 229.3 0 316.8s229.3 87.5 316.8 0c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0c-62.5 62.5-163.8 62.5-226.3 0s-62.5-163.8 0-226.3c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5z"
                    />
                </svg>
                <span class="hidden sm:inline">{{ __(t('refresh')) }}</span>
            </button>

            <div class="relative ms-2 inline-block text-start" ref="dropdownRef">
                <button
                    @click.stop="toggleUserDropdown"
                    class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full border border-sky-200 bg-sky-100 text-sky-600 shadow-sm transition-all hover:bg-sky-200 dark:border-sky-800 dark:bg-sky-900/50 dark:text-sky-400 dark:hover:bg-sky-900"
                >
                    <img
                        v-if="$page.props.auth.user.profile_image"
                        :src="'/storage/' + $page.props.auth.user.profile_image"
                        alt="User"
                        class="h-full w-full object-cover"
                    />
                    <img
                        v-else
                        :src="`https://ui-avatars.com/api/?name=${encodeURIComponent($page.props.auth.user.name)}&background=random&color=fff`"
                        alt="User"
                        class="h-full w-full object-cover"
                    />
                </button>

                <!-- Dropdown -->
                <div
                    v-show="isUserDropdownOpen"
                    class="dropdown-animate absolute end-0 z-50 mt-3 w-56 origin-top-right rounded-2xl border border-slate-100 bg-white py-2 shadow-2xl dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="border-b border-slate-50 px-4 py-3 text-start dark:border-slate-800">
                        <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">{{ __(t('signed_in_as')) }}</p>
                        <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ $page.props.auth.user.name }}</p>
                    </div>

                    <!-- Mobile Actions (Visible inside dropdown on small screens) -->
                    <div class="border-b border-slate-50 p-1 md:hidden dark:border-slate-800">
                        <button
                            @click="toggleTheme"
                            class="flex w-full items-center space-x-3 rounded-xl px-3 py-2 text-start text-sm font-medium text-slate-600 transition-all hover:bg-slate-50 hover:text-sky-600 rtl:space-x-reverse dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-sky-400"
                        >
                            <svg v-if="isDark" class="h-4 w-4 text-center" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                                ></path>
                            </svg>
                            <svg v-else class="h-4 w-4 text-center" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                                ></path>
                            </svg>
                            <span>{{ isDark ? __('Light Mode') : __('Dark Mode') }}</span>
                        </button>
                    </div>

                    <div class="p-1">
                        <Link
                            :href="customer.profile.edit.url()"
                            class="flex w-full items-center space-x-3 rounded-xl px-3 py-2 text-start text-sm font-medium text-slate-600 transition-all hover:bg-slate-50 hover:text-sky-600 rtl:space-x-reverse dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-sky-400"
                        >
                            <svg class="h-4 w-4 text-center" fill="currentColor" viewBox="0 0 512 512">
                                <path
                                    d="M495.9 166.6c3.2 8.7 .5 18.4-6.4 24.6l-43.3 39.4c1.1 8.3 1.7 16.8 1.7 25.4s-.6 17.1-1.7 25.4l43.3 39.4c6.9 6.2 9.6 15.9 6.4 24.6c-4.4 11.9-9.7 23.3-15.8 34.3l-4.7 8.1c-6.6 11-14 21.4-22.1 31.2c-5.9 7.2-15.7 9.6-24.5 6.8l-55.7-17.7c-13.4 10.3-28.2 18.9-44 25.4l-12.5 57.1c-2 9.1-9 16.3-18.2 17.8c-13.8 2.3-28 3.5-42.5 3.5s-28.7-1.2-42.5-3.5c-9.2-1.5-16.2-8.7-18.2-17.8l-12.5-57.1c-15.8-6.5-30.6-15.1-44-25.4L83.1 425.9c-8.8 2.8-18.6 .3-24.5-6.8c-8.1-9.8-15.5-20.2-22.1-31.2l-4.7-8.1c-6.1-11-11.4-22.4-15.8-34.3c-3.2-8.7-.5-18.4 6.4-24.6l43.3-39.4C64.6 273.1 64 264.6 64 256s.6-17.1 1.7-25.4L22.4 191.2c-6.9-6.2-9.6-15.9-6.4-24.6c4.4-11.9 9.7-23.3 15.8-34.3l4.7-8.1c6.6-11 14-21.4 22.1-31.2c5.9-7.2 15.7-9.6 24.5-6.8l55.7 17.7c13.4-10.3 28.2-18.9 44-25.4l12.5-57.1c2-9.1 9-16.3 18.2-17.8C227.3 1.2 241.7 0 256 0s28.7 1.2 42.5 3.5c9.2 1.5 16.2 8.7 18.2 17.8l12.5 57.1c15.8 6.5 30.6 15.1 44 25.4l55.7-17.7c8.8-2.8 18.6-.3 24.5 6.8c8.1 9.8 15.5 20.2 22.1 31.2l4.7 8.1c6.1 11 11.4 22.4 15.8 34.3zM256 336a80 80 0 1 0 0-160 80 80 0 1 0 0 160z"
                                />
                            </svg>
                            <span>{{ __(t('settings')) }}</span>
                        </Link>
                        <Link
                            :href="logout.url()"
                            method="post"
                            as="button"
                            class="mt-1 flex w-full items-center space-x-3 rounded-xl px-3 py-2 text-start text-sm font-bold text-rose-500 transition-all hover:bg-rose-50 rtl:space-x-reverse dark:hover:bg-rose-950/30"
                        >
                            <svg class="h-4 w-4 text-center rtl:scale-x-[-1]" fill="currentColor" viewBox="0 0 512 512">
                                <path
                                    d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"
                                />
                            </svg>
                            <span>{{ __(t('logout')) }}</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<style scoped>
.dropdown-animate {
    transform-origin: top right;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
[dir='rtl'] .dropdown-animate {
    transform-origin: top left;
}
</style>
