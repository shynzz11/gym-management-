<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { LayoutDashboard, User, ChevronLeft, LifeBuoy, Wallet, Zap } from 'lucide-vue-next';
import customer from '@/routes/customer';

const props = defineProps<{
    isCollapsed: boolean;
}>();

const emit = defineEmits(['toggleCollapse', 'update:activeSection']);

const page = usePage();

const normalizePath = (url: string): string => {
    if (!url) {
        return '/';
    }

    try {
        const parsedUrl = url.startsWith('http://') || url.startsWith('https://') ? new URL(url) : new URL(url, window.location.origin);
        const normalizedPath = parsedUrl.pathname.replace(/\/+$/, '');

        return normalizedPath === '' ? '/' : normalizedPath;
    } catch {
        const [pathWithoutQuery] = url.split('?');
        const normalizedPath = (pathWithoutQuery || '/').replace(/\/+$/, '');

        return normalizedPath === '' ? '/' : normalizedPath;
    }
};

const currentPath = computed(() => normalizePath(page.url));

const isLinkActive = (targetUrl: string): boolean => {
    if (!targetUrl || targetUrl === '#') {
        return false;
    }

    const targetPath = normalizePath(targetUrl);

    if (targetPath === '/') {
        return currentPath.value === '/';
    }

    return currentPath.value === targetPath || currentPath.value.startsWith(`${targetPath}/`);
};

// Initialize active section based on current URL
const getInitialSection = () => {
    const url = normalizePath(page.url);

    if (url.startsWith('/customer/profile')) {
        return 'profile';
    }

    if (url.startsWith('/customer/dashboard') || url === '/customer') {
        return 'dashboard';
    }

    if (url.startsWith('/customer/transactions')) {
        return 'dashboard';
    }
    // Default fallback
    return 'dashboard';
};

const activeSection = ref(getInitialSection());

const switchSection = (section: string) => {
    activeSection.value = section;
    emit('update:activeSection', section);
};

// Branding helper
const t = (key: string) => {
    if (key === 'tier_title') return page.props.branding?.customer?.sidebar?.tier_title || 'customer_tier_title';
    if (key === 'tier_subtitle') return page.props.branding?.customer?.sidebar?.tier_subtitle || 'customer_tier_subtitle';
    return page.props.branding?.customer?.sidebar?.[key] || key;
};

watch(
    () => page.url,
    () => {
        activeSection.value = getInitialSection();
    },
);
</script>

<template>
    <aside
        id="customer-sidebar"
        class="fixed inset-y-0 start-0 top-0 z-50 flex h-screen border-e border-admin-sidebar-border bg-admin-sidebar transition-all duration-300 ease-in-out lg:sticky dark:border-admin-sidebar-border-dark dark:bg-admin-sidebar-dark"
        :class="{ 'w-[72px]': isCollapsed, 'w-[300px]': !isCollapsed }"
    >
        <!-- TIER 1: Command Rail -->
        <div class="z-20 flex w-[72px] flex-shrink-0 flex-col items-center space-y-6 bg-admin-sidebar-rail py-4 dark:bg-admin-sidebar-rail-dark">
            <Link
                :href="customer.dashboard.url()"
                class="flex h-10 w-10 cursor-pointer items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg transition-colors hover:brightness-110 dark:border-slate-700 dark:bg-slate-800"
            >
                <img
                    v-if="$page.props.branding.business_settings?.logo_url"
                    :src="
                        $page.props.branding.business_settings.logo_url.startsWith('http')
                            ? $page.props.branding.business_settings.logo_url
                            : '/storage/' + $page.props.branding.business_settings.logo_url
                    "
                    class="h-full w-full object-contain p-1"
                />
                <Zap v-else :size="20" fill="currentColor" class="text-[var(--brand-primary)]" />
            </Link>

            <div class="flex w-full flex-1 flex-col items-center space-y-2">
                <!-- Dashboard -->
                <button
                    @click="switchSection('dashboard')"
                    class="admin-rail-btn group relative flex w-full flex-col items-center py-3 transition-all"
                    :class="activeSection === 'dashboard' ? 'active-rail' : 'rail-icon hover:brightness-110'"
                >
                    <LayoutDashboard :size="20" class="mb-1" />
                    <span class="rail-tooltip">{{ __(t('dashboard')) }}</span>
                </button>

                <!-- Profile -->
                <button
                    @click="switchSection('profile')"
                    class="admin-rail-btn group relative flex w-full flex-col items-center py-3 transition-all"
                    :class="activeSection === 'profile' ? 'active-rail' : 'rail-icon hover:brightness-110'"
                >
                    <User :size="20" class="mb-1" />
                    <span class="rail-tooltip">{{ __(t('profile')) }}</span>
                </button>
            </div>

            <div class="flex w-full flex-col items-center space-y-4 border-t border-slate-800/50 px-4 pt-4">
                <button
                    @click="emit('toggleCollapse')"
                    class="rail-icon group relative hidden h-10 w-10 items-center justify-center rounded-xl transition-all hover:bg-slate-800 hover:text-sky-400 lg:flex"
                >
                    <ChevronLeft :size="20" :class="{ 'rotate-180': isCollapsed }" />
                    <span class="rail-tooltip">{{ __(t('collapse')) }}</span>
                </button>
                <button
                    class="rail-icon group relative flex h-10 w-10 items-center justify-center rounded-xl transition-all hover:bg-slate-800 hover:text-sky-400"
                >
                    <LifeBuoy :size="20" />
                    <span class="rail-tooltip">{{ __(t('support')) }}</span>
                </button>
            </div>
        </div>

        <!-- TIER 2: Sub-menu -->
        <div
            id="tier-2-container"
            class="flex h-full flex-shrink-0 flex-col overflow-hidden bg-admin-sidebar transition-all duration-300 ease-in-out dark:bg-admin-sidebar-dark"
            :class="isCollapsed ? 'pointer-events-none w-0 opacity-0' : 'w-[228px] opacity-100'"
        >
            <div class="border-sidebar-border dark:border-sidebar-border-dark border-b p-5">
                <h2
                    class="line-clamp-1 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white"
                    :title="$page.props.branding.business_settings?.business_name"
                >
                    {{ $page.props.branding.business_settings?.business_name || __(t('tier_title')) }}
                </h2>
                <p class="mt-1.5 text-xs leading-snug font-semibold text-slate-500">
                    {{ $page.props.branding.business_settings?.tagline || __(t('tier_subtitle')) }}
                </p>
            </div>

            <div class="flex-1 space-y-1 overflow-y-auto p-4">
                <!-- Dashboard Section -->
                <div v-if="activeSection === 'dashboard'" class="animate-fade-in">
                    <div class="mb-3 px-4 text-[11px] font-bold tracking-widest text-slate-400 uppercase">{{ __(t('main_menu')) }}</div>
                    <Link
                        :href="customer.dashboard.url()"
                        class="flex items-center space-x-3 rounded-lg px-3 py-2.5 text-sm whitespace-nowrap transition-all"
                        :class="
                            isLinkActive(customer.dashboard.url())
                                ? 'nav-link-active bg-admin-active font-bold text-admin-active-text'
                                : 'text-slate-500 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800'
                        "
                    >
                        <LayoutDashboard :size="16" />
                        <span>{{ __(t('dashboard')) }}</span>
                    </Link>

                    <Link
                        :href="customer.transactions.index.url()"
                        class="mt-1 flex items-center space-x-3 rounded-lg px-3 py-2.5 text-sm whitespace-nowrap transition-all"
                        :class="
                            isLinkActive(customer.transactions.index.url())
                                ? 'nav-link-active bg-admin-active font-bold text-admin-active-text'
                                : 'text-slate-500 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800'
                        "
                    >
                        <Wallet :size="16" />
                        <span>{{ __('Transactions History') }}</span>
                    </Link>
                </div>

                <!-- Profile Section -->
                <div v-if="activeSection === 'profile'" class="animate-fade-in space-y-1">
                    <div class="mb-3 px-4">
                        <h2 class="text-sm font-extrabold tracking-tight text-slate-900 dark:text-white">{{ __(t('account_settings')) }}</h2>
                        <p class="mt-1 text-[10px] font-bold tracking-[0.2em] text-slate-400 uppercase">{{ __(t('manage_profile')) }}</p>
                    </div>

                    <Link
                        :href="customer.profile.edit.url()"
                        class="flex items-center space-x-3 rounded-lg px-3 py-2.5 text-sm whitespace-nowrap transition-all"
                        :class="
                            isLinkActive(customer.profile.edit.url())
                                ? 'nav-link-active bg-admin-active font-bold text-admin-active-text'
                                : 'text-slate-500 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800'
                        "
                    >
                        <User :size="16" :class="{ 'opacity-70': !isLinkActive(customer.profile.edit.url()) }" />
                        <span class="text-sm">{{ __(t('my_profile')) }}</span>
                    </Link>
                </div>
            </div>
        </div>
    </aside>
</template>

<style scoped>
.active-rail {
    background-color: var(--admin-active-item-bg);
    color: var(--admin-active-item-text) !important;
    border-right: 4px solid var(--admin-active-item-border);
}
:global(.dark) .active-rail {
    background-color: var(--admin-active-item-bg-dark);
    color: var(--admin-active-item-text-dark) !important;
    border-right: 4px solid var(--admin-active-item-border-dark);
}

[dir='rtl'] .active-rail {
    border-right: none;
    border-left: 4px solid var(--admin-active-item-border);
}
[dir='rtl'] :global(.dark) .active-rail {
    border-left: 4px solid var(--admin-active-item-border-dark);
}

.nav-link-active {
    background-color: var(--admin-active-item-bg);
    color: var(--admin-active-item-text);
    font-weight: 700;
}
:global(.dark) .nav-link-active {
    background-color: var(--admin-active-item-bg-dark);
    color: var(--admin-active-item-text-dark);
}

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

.rail-icon {
    color: var(--admin-sidebar-icon);
}
:global(.dark) .rail-icon {
    color: var(--admin-sidebar-icon-dark);
}

.rail-tooltip {
    pointer-events: none;
    position: absolute;
    inset-inline-start: 70px;
    top: 50%;
    z-index: 60;
    border-radius: 0.5rem;
    background-color: rgb(30 41 59);
    padding: 0.375rem 0.75rem;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-50%) translateX(-6px);
    transition:
        opacity 0.15s ease,
        transform 0.15s ease,
        visibility 0.15s ease;
}

.group:hover .rail-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateY(-50%) translateX(0);
}

[dir='rtl'] .rail-tooltip {
    inset-inline-start: auto;
    inset-inline-end: 70px;
    transform: translateY(-50%) translateX(6px);
}

[dir='rtl'] .group:hover .rail-tooltip {
    transform: translateY(-50%) translateX(0);
}
</style>
