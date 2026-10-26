<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { contact } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { privacy, terms, about } from '@/routes/legal';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import Toast from '@/Components/Common/Toast.vue';
import { computed, onMounted, ref } from 'vue';
import { Menu, X } from 'lucide-vue-next';

const page = usePage();
const user = computed(() => (page.props as any).auth?.user);
const businessSettings = computed(() => (page.props.branding as any)?.business_settings || {});

const logoUrl = computed(() => {
    const logo = businessSettings.value.logo_url;
    const defaultLogo = 'https://api.dicebear.com/7.x/initials/svg?seed=Nexus&backgroundColor=0284c7';
    const src = logo || defaultLogo;
    return src.startsWith('http') ? src : '/storage/' + src;
});

const brandName = computed(() => businessSettings.value.business_name || 'GymOS');
const brandTagline = computed(() => businessSettings.value.tagline || 'Next-Gen Gym Management');

const dashboardUrl = computed(() => {
    if (!user.value) return '#';
    return adminDashboard.url();
});

const isMobileMenuOpen = ref(false);

onMounted(() => {
    const direction = localStorage.getItem('direction') || 'ltr';
    document.documentElement.setAttribute('dir', direction);
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white font-sans text-gray-900 transition-colors duration-300 dark:bg-gray-950 dark:text-white">
        <Toast />

        <!-- Navigation -->
        <nav class="sticky top-0 z-50 border-b border-gray-200 bg-white/90 backdrop-blur-md dark:border-gray-800 dark:bg-gray-950/90">
            <div class="container mx-auto flex items-center justify-between px-6 py-4">
                <!-- Logo + Brand -->
                <Link href="/" class="flex items-center gap-2 transition-opacity hover:opacity-80">
                    <img :src="logoUrl" class="h-9 w-auto flex-shrink-0 object-contain" :alt="brandName" />
                    <div class="flex flex-col text-start leading-tight">
                        <span class="text-lg font-black tracking-tighter text-gray-900 dark:text-white">{{ brandName }}</span>
                        <span v-if="brandTagline" class="hidden text-[10px] text-gray-400 sm:block dark:text-gray-500">{{ brandTagline }}</span>
                    </div>
                </Link>

                <!-- Desktop Nav Links -->
                <div class="hidden items-center gap-6 text-sm font-semibold text-gray-600 md:flex dark:text-gray-400">
                    <Link :href="about.url()" class="transition-colors hover:text-primary-600 dark:hover:text-primary-400">{{ __('About Us') }}</Link>
                    <Link :href="privacy.url()" class="transition-colors hover:text-primary-600 dark:hover:text-primary-400">{{
                        __('Privacy')
                    }}</Link>
                    <Link :href="terms.url()" class="transition-colors hover:text-primary-600 dark:hover:text-primary-400">{{ __('Terms') }}</Link>
                    <Link :href="contact.url()" class="transition-colors hover:text-primary-600 dark:hover:text-primary-400">{{
                        __('Contact')
                    }}</Link>
                </div>

                <!-- Desktop Auth + Tools -->
                <div class="hidden items-center gap-3 md:flex">
                    <ThemeToggle />
                    <template v-if="user">
                        <Link
                            :href="dashboardUrl"
                            class="px-3 py-2 text-sm font-bold text-gray-700 transition-colors hover:text-primary-600 dark:text-gray-200 dark:hover:text-primary-400"
                        >
                            {{ __('Dashboard') }}
                        </Link>
                    </template>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex items-center gap-3 md:hidden">
                    <ThemeToggle />
                    <button
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        class="text-gray-600 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400"
                    >
                        <Menu v-if="!isMobileMenuOpen" class="h-6 w-6" />
                        <X v-else class="h-6 w-6" />
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div
                v-show="isMobileMenuOpen"
                class="space-y-3 border-t border-gray-200 bg-white px-6 py-4 md:hidden dark:border-gray-800 dark:bg-gray-950"
            >
                <Link
                    :href="about.url()"
                    class="block py-1 text-sm font-semibold text-gray-600 transition-colors hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
                    >{{ __('About Us') }}</Link
                >
                <Link
                    :href="privacy.url()"
                    class="block py-1 text-sm font-semibold text-gray-600 transition-colors hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
                    >{{ __('Privacy Policy') }}</Link
                >
                <Link
                    :href="terms.url()"
                    class="block py-1 text-sm font-semibold text-gray-600 transition-colors hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
                    >{{ __('Terms & Conditions') }}</Link
                >
                <Link
                    :href="contact.url()"
                    class="block py-1 text-sm font-semibold text-gray-600 transition-colors hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
                    >{{ __('Contact Us') }}</Link
                >
                <div class="flex flex-col gap-3 border-t border-gray-100 pt-3 dark:border-gray-800">
                    <template v-if="user">
                        <Link
                            :href="dashboardUrl"
                            class="rounded-xl bg-primary-50 px-4 py-2 text-center text-sm font-bold text-primary-600 dark:bg-primary-900/10 dark:text-primary-400"
                        >
                            {{ __('Dashboard') }}
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <main class="flex-grow">
            <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-gray-50 py-10 dark:border-gray-800 dark:bg-gray-950">
            <div class="container mx-auto px-6">
                <div class="flex flex-col items-center justify-between gap-6 md:flex-row">
                    <!-- Brand -->
                    <div class="flex items-center gap-2">
                        <img :src="logoUrl" class="h-7 w-auto flex-shrink-0 object-contain" :alt="brandName" />
                        <span class="font-black tracking-tight text-gray-900 dark:text-white">{{ brandName }}</span>
                    </div>

                    <!-- Legal Links -->
                    <div class="flex flex-wrap justify-center gap-6 text-sm font-medium text-gray-500 dark:text-gray-400">
                        <Link :href="about.url()" class="transition-colors hover:text-primary-600 dark:hover:text-primary-400">{{
                            __('About Us')
                        }}</Link>
                        <Link :href="privacy.url()" class="transition-colors hover:text-primary-600 dark:hover:text-primary-400">{{
                            __('Privacy Policy')
                        }}</Link>
                        <Link :href="terms.url()" class="transition-colors hover:text-primary-600 dark:hover:text-primary-400">{{
                            __('Terms & Conditions')
                        }}</Link>
                        <Link :href="contact.url()" class="transition-colors hover:text-primary-600 dark:hover:text-primary-400">{{
                            __('Contact Us')
                        }}</Link>
                    </div>

                    <!-- Copyright -->
                    <p class="text-sm text-gray-400 dark:text-gray-600">
                        &copy; {{ new Date().getFullYear() }} {{ brandName }}. {{ __('All rights reserved.') }}
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
