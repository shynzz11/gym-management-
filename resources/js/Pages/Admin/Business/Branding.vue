<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    Palette,
    Briefcase,
    Save,
    Link as LinkIcon,
    Upload,
    Info,
    Mail,
    Phone,
    MapPin,
    Facebook,
    Twitter,
    Instagram,
} from 'lucide-vue-next';
import { ref, onMounted, watch } from 'vue';
import { update } from '@/actions/App/Http/Controllers/Admin/BusinessSettingController';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
    default_colors: {
        type: Object,
        default: () => ({
            primary: '#315df5',
            primary_light: '#eff6ff',
            primary_dark_text: '#93c5fd',
            secondary: '#64748b',
            sidebar_rail_bg: '#1e3a8a',
            sidebar_rail_bg_dark: '#172554',
            sidebar_icon_color: '#ffffff',
            sidebar_icon_color_dark: '#ffffff',
        }),
    },
});

const page = usePage<{ name?: unknown }>();

const resolveDefaultBusinessName = (): string => {
    const sharedName = page.props.name;

    if (typeof sharedName === 'string' && sharedName.trim().length > 0) {
        return sharedName.trim();
    }

    return '';
};

const defaultSidebarRailBg = props.default_colors.sidebar_rail_bg || props.default_colors.admin?.sidebar_rail_bg || '#1e3a8a';
const defaultSidebarRailBgDark = props.default_colors.sidebar_rail_bg_dark || props.default_colors.admin?.sidebar_rail_bg_dark || '#172554';
const defaultSidebarIconColor = props.default_colors.sidebar_icon_color || props.default_colors.admin?.sidebar_icon_color || '#ffffff';
const defaultSidebarIconColorDark = props.default_colors.sidebar_icon_color_dark || props.default_colors.admin?.sidebar_icon_color_dark || '#ffffff';

const defaultAdminSidebarBg = props.default_colors.admin_sidebar_bg || props.default_colors.admin?.sidebar_bg || '#ffffff';
const defaultAdminSidebarBgDark = props.default_colors.admin_sidebar_bg_dark || props.default_colors.admin?.sidebar_bg_dark || '#0f172a';
const defaultAdminSidebarBorder = props.default_colors.admin_sidebar_border || props.default_colors.admin?.sidebar_border || '#e2e8f0';
const defaultAdminSidebarBorderDark = props.default_colors.admin_sidebar_border_dark || props.default_colors.admin?.sidebar_border_dark || '#1e293b';

const normalizeFaviconValue = (value?: string | null): string => {
    return (value ?? '').trim();
};

const defaultFaviconUrl = 'https://api.dicebear.com/7.x/initials/svg?seed=N';

const resolveFaviconUrl = (value?: string | null): string => {
    const normalizedValue = normalizeFaviconValue(value);

    if (normalizedValue === '') {
        return defaultFaviconUrl;
    }

    if (normalizedValue.startsWith('http://') || normalizedValue.startsWith('https://')) {
        return normalizedValue;
    }

    if (normalizedValue.startsWith('/storage/')) {
        return normalizedValue;
    }

    const relativePath = normalizedValue.startsWith('storage/') ? normalizedValue.slice('storage/'.length) : normalizedValue.replace(/^\/+/, '');

    return `/storage/${relativePath}`;
};

const applyFavicon = (value?: string | null): void => {
    const faviconHref = resolveFaviconUrl(value);
    let iconElement = document.head.querySelector<HTMLLinkElement>('link[rel="icon"]');

    if (!iconElement) {
        iconElement = document.createElement('link');
        iconElement.rel = 'icon';
        iconElement.setAttribute('sizes', 'any');
        document.head.appendChild(iconElement);
    }

    iconElement.href = faviconHref;
};

const applyBrandingCssVariables = (settings: Record<string, string | null | undefined>): void => {
    const root = document.documentElement;

    root.style.setProperty('--brand-primary', settings.primary || props.default_colors.primary);
    root.style.setProperty('--brand-secondary', settings.secondary || props.default_colors.secondary);
    root.style.setProperty('--admin-sidebar-bg', settings.admin_sidebar_bg || defaultAdminSidebarBg);
    root.style.setProperty('--admin-sidebar-bg-dark', settings.admin_sidebar_bg_dark || defaultAdminSidebarBgDark);
    root.style.setProperty('--admin-sidebar-border', settings.admin_sidebar_border || defaultAdminSidebarBorder);
    root.style.setProperty('--admin-sidebar-border-dark', settings.admin_sidebar_border_dark || defaultAdminSidebarBorderDark);
    root.style.setProperty('--admin-sidebar-rail-bg', settings.sidebar_rail_bg || defaultSidebarRailBg);
    root.style.setProperty('--admin-sidebar-rail-bg-dark', settings.sidebar_rail_bg_dark || defaultSidebarRailBgDark);
    root.style.setProperty('--admin-sidebar-icon', settings.sidebar_icon_color || defaultSidebarIconColor);
    root.style.setProperty('--admin-sidebar-icon-dark', settings.sidebar_icon_color_dark || defaultSidebarIconColorDark);
    root.style.setProperty('--admin-active-item-bg', settings.primary_light || props.default_colors.primary_light);
    root.style.setProperty('--admin-active-item-text-dark', settings.primary_dark_text || props.default_colors.primary_dark_text);
};

const form = useForm({
    business_name: props.settings.business_name || resolveDefaultBusinessName(),
    tagline: props.settings.tagline || 'Next-Gen Gym Management',

    logo_url: props.settings.logo_url || 'https://api.dicebear.com/7.x/initials/svg?seed=Nexus&backgroundColor=0284c7',
    favicon_url: normalizeFaviconValue(props.settings.favicon_url) || defaultFaviconUrl,

    primary: props.settings.primary || props.default_colors.primary,
    primary_light: props.settings.primary_light || props.default_colors.primary_light,
    primary_dark_text: props.settings.primary_dark_text || props.default_colors.primary_dark_text,

    secondary: props.settings.secondary || props.default_colors.secondary,

    admin_sidebar_bg: props.settings.admin_sidebar_bg || defaultAdminSidebarBg,
    admin_sidebar_bg_dark: props.settings.admin_sidebar_bg_dark || defaultAdminSidebarBgDark,
    admin_sidebar_border: props.settings.admin_sidebar_border || defaultAdminSidebarBorder,
    admin_sidebar_border_dark: props.settings.admin_sidebar_border_dark || defaultAdminSidebarBorderDark,

    sidebar_rail_bg: props.settings.sidebar_rail_bg || defaultSidebarRailBg,
    sidebar_rail_bg_dark: props.settings.sidebar_rail_bg_dark || defaultSidebarRailBgDark,
    sidebar_icon_color: props.settings.sidebar_icon_color || defaultSidebarIconColor,
    sidebar_icon_color_dark: props.settings.sidebar_icon_color_dark || defaultSidebarIconColorDark,

    contact_email: props.settings.contact_email || 'contact@gymos.io',
    contact_phone: props.settings.contact_phone || '+1 (555) 000-1234',
    contact_address: props.settings.contact_address || '123 Fitness Ave, San Francisco, CA 94103',

    social_facebook: props.settings.social_facebook || '',
    social_twitter: props.settings.social_twitter || '',
    social_instagram: props.settings.social_instagram || '',
});

// File inputs handling
const logoInput = ref<HTMLInputElement | null>(null);
const faviconInput = ref<HTMLInputElement | null>(null);
const logoFile = ref<File | null>(null);
const faviconFile = ref<File | null>(null);

const clearUploadedFiles = () => {
    if (logoFile.value && previews.value.logo_url.startsWith('blob:')) {
        URL.revokeObjectURL(previews.value.logo_url);
    }
    if (faviconFile.value && previews.value.favicon_url.startsWith('blob:')) {
        URL.revokeObjectURL(previews.value.favicon_url);
    }

    logoFile.value = null;
    faviconFile.value = null;
};

const handleFileChange = (event: Event, key: keyof typeof form) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        const url = URL.createObjectURL(file);

        if (key === 'logo_url') {
            if (logoFile.value) {
                URL.revokeObjectURL(previews.value.logo_url);
            }
            logoFile.value = file;
        } else if (key === 'favicon_url') {
            if (faviconFile.value) {
                URL.revokeObjectURL(previews.value.favicon_url);
            }
            faviconFile.value = file;
        }
        previews.value[key] = url;
    }
};

const previews = ref<Record<string, string>>({
    logo_url: props.settings.logo_url || form.logo_url,
    favicon_url: normalizeFaviconValue(props.settings.favicon_url) || defaultFaviconUrl,
});

watch(
    () => props.settings,
    (newSettings) => {
        form.business_name = newSettings.business_name || resolveDefaultBusinessName();
        form.tagline = newSettings.tagline || 'Next-Gen Gym Management';
        form.logo_url = newSettings.logo_url || 'https://api.dicebear.com/7.x/initials/svg?seed=Nexus&backgroundColor=0284c7';
        const normalizedFaviconUrl = normalizeFaviconValue(newSettings.favicon_url);

        form.favicon_url = normalizedFaviconUrl || defaultFaviconUrl;

        form.primary = newSettings.primary || props.default_colors.primary;
        form.primary_light = newSettings.primary_light || props.default_colors.primary_light;
        form.primary_dark_text = newSettings.primary_dark_text || props.default_colors.primary_dark_text;

        form.secondary = newSettings.secondary || props.default_colors.secondary;

        form.admin_sidebar_bg = newSettings.admin_sidebar_bg || defaultAdminSidebarBg;
        form.admin_sidebar_bg_dark = newSettings.admin_sidebar_bg_dark || defaultAdminSidebarBgDark;
        form.admin_sidebar_border = newSettings.admin_sidebar_border || defaultAdminSidebarBorder;
        form.admin_sidebar_border_dark = newSettings.admin_sidebar_border_dark || defaultAdminSidebarBorderDark;

        form.sidebar_rail_bg = newSettings.sidebar_rail_bg || defaultSidebarRailBg;
        form.sidebar_rail_bg_dark = newSettings.sidebar_rail_bg_dark || defaultSidebarRailBgDark;
        form.sidebar_icon_color = newSettings.sidebar_icon_color || defaultSidebarIconColor;
        form.sidebar_icon_color_dark = newSettings.sidebar_icon_color_dark || defaultSidebarIconColorDark;

        form.contact_email = newSettings.contact_email || 'contact@gymos.io';
        form.contact_phone = newSettings.contact_phone || '+1 (555) 000-1234';
        form.contact_address = newSettings.contact_address || '123 Fitness Ave, San Francisco, CA 94103';
        form.social_facebook = newSettings.social_facebook || '';
        form.social_twitter = newSettings.social_twitter || '';
        form.social_instagram = newSettings.social_instagram || '';

        clearUploadedFiles();
        previews.value = {
            logo_url: newSettings.logo_url || form.logo_url,
            favicon_url: normalizedFaviconUrl || defaultFaviconUrl,
        };
        applyBrandingCssVariables(newSettings as Record<string, string | null | undefined>);
    },
    { deep: true },
);

const submit = () => {
    form.transform((data) => ({
        ...data,
        logo_url: logoFile.value ?? data.logo_url,
        favicon_url: faviconFile.value ?? data.favicon_url,
    })).post(update.url(), {
        preserveScroll: true,
        onSuccess: (page) => {
            const serverSettings = (page.props as any)?.settings || (page.props as any)?.branding?.business_settings || {};

            applyFavicon(serverSettings.favicon_url);
            applyBrandingCssVariables(serverSettings);

            clearUploadedFiles();
            previews.value.logo_url = serverSettings.logo_url || form.logo_url;
            previews.value.favicon_url = resolveFaviconUrl(serverSettings.favicon_url);
        },
    });
};

// UI Helpers
const triggerFileInput = (refName: 'logoInput' | 'faviconInput') => {
    if (refName === 'logoInput') logoInput.value?.click();
    if (refName === 'faviconInput') faviconInput.value?.click();
};

onMounted(() => {
    applyFavicon(props.settings.favicon_url);
    applyBrandingCssVariables(props.settings as Record<string, string | null | undefined>);
});

watch(
    () => form.favicon_url,
    (newFavicon) => {
        if (typeof newFavicon !== 'string') {
            return;
        }

        previews.value.favicon_url = resolveFaviconUrl(newFavicon);
        applyFavicon(newFavicon);
    },
);
</script>

<template>
    <Head :title="__('Business Branding')" />

    <AdminLayout>
        <div class="h-full">
            <!-- BUSINESS PANE -->
            <div class="animate-fade-in mx-auto max-w-6xl space-y-6">
                <!-- Header -->
                <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ __('business_branding') }}</h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ __("Manage your organization's visual identity and public profile.") }}
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            @click="submit"
                            :disabled="form.processing"
                            class="flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold text-white shadow-lg transition-all hover:opacity-90 disabled:opacity-70"
                            :style="{
                                backgroundColor: 'var(--brand-primary)',
                                boxShadow: '0 10px 15px -3px var(--brand-primary-transparent, rgba(0,0,0,0.1))',
                            }"
                        >
                            <Save v-if="!form.processing" :size="18" />
                            <span v-if="form.processing">{{ __('Saving...') }}</span>
                            <span v-else>{{ __('Save Changes') }}</span>
                        </button>
                    </div>
                </div>

                <!-- 1. HEADER & COVER SECTION -->
                <div
                    class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm transition-all duration-300 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="relative flex items-center gap-6 bg-slate-50 p-6 dark:bg-slate-800">
                        <div class="group/logo relative flex-shrink-0">
                            <img
                                :src="previews.logo_url"
                                class="h-24 w-24 rounded-[2rem] border-4 border-white bg-white object-contain p-2 shadow-2xl dark:border-slate-900 dark:bg-slate-800"
                            />
                            <button
                                @click="triggerFileInput('logoInput')"
                                class="absolute inset-0 flex cursor-pointer items-center justify-center rounded-[2rem] bg-black/40 text-xs font-bold text-white opacity-0 transition-all group-hover/logo:opacity-100"
                            >
                                {{ __('Edit') }}
                            </button>
                            <input type="file" ref="logoInput" class="hidden" accept="image/*" @change="(e) => handleFileChange(e, 'logo_url')" />
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white">{{ form.business_name }}</h3>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ form.tagline }}</p>
                        </div>
                    </div>
                    <div class="px-5 pt-8 pb-5">
                        <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="mb-2 block ps-1 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase">{{
                                    __('Business Name')
                                }}</label>
                                <input
                                    v-model="form.business_name"
                                    type="text"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 font-medium text-slate-900 transition-all outline-none focus:opacity-100 focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                    :style="{ '--tw-ring-color': 'var(--brand-primary)' }"
                                />
                            </div>
                            <div class="space-y-2">
                                <label class="mb-2 block ps-1 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase">{{
                                    __('Tagline / Slogan')
                                }}</label>
                                <input
                                    v-model="form.tagline"
                                    type="text"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 font-medium text-slate-900 transition-all outline-none focus:opacity-100 focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                    :style="{ '--tw-ring-color': 'var(--brand-primary)' }"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. VISUAL ASSETS & COLORS -->
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <!-- Assets -->
                    <div
                        class="space-y-5 rounded-[2rem] border border-slate-100 bg-white p-5 shadow-sm transition-all duration-300 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <h4 class="mb-6 flex items-center text-sm font-black tracking-widest text-slate-400 uppercase">
                            <LinkIcon class="me-3" :size="16" :style="{ color: 'var(--brand-primary)' }" /> {{ __('Asset Resources') }}
                        </h4>
                        <div class="space-y-5">
                            <div>
                                <label class="mb-2 block ps-1 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase">{{
                                    __('Logo URL')
                                }}</label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="form.logo_url"
                                        type="text"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-medium text-slate-900 transition-all outline-none focus:opacity-100 focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        :style="{ '--tw-ring-color': 'var(--brand-primary)' }"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block ps-1 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase">{{
                                    __('Favicon')
                                }}</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="form.favicon_url"
                                        type="text"
                                        :placeholder="__('Enter URL or upload a file')"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-medium text-slate-900 transition-all outline-none focus:opacity-100 focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        :style="{ '--tw-ring-color': 'var(--brand-primary)' }"
                                    />
                                    <!-- Clickable favicon preview / upload -->
                                    <div class="group/fav relative flex-shrink-0">
                                        <div
                                            class="flex h-10 w-10 cursor-pointer items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-800"
                                            @click="triggerFileInput('faviconInput')"
                                        >
                                            <img v-if="previews.favicon_url" :src="previews.favicon_url" class="h-full w-full object-contain" />
                                            <Upload v-else :size="14" class="text-slate-400" />
                                        </div>
                                        <div
                                            class="absolute inset-0 flex cursor-pointer items-center justify-center rounded-lg bg-black/40 opacity-0 transition-all group-hover/fav:opacity-100"
                                            @click="triggerFileInput('faviconInput')"
                                        >
                                            <Upload :size="12" class="text-white" />
                                        </div>
                                        <input
                                            type="file"
                                            ref="faviconInput"
                                            class="hidden"
                                            accept="image/*,.ico"
                                            @change="(e) => handleFileChange(e, 'favicon_url')"
                                        />
                                    </div>
                                </div>
                                <p class="mt-1.5 ps-1 text-[10px] text-slate-400">{{ __('Recommended: 32×32px .ico, .png, or .svg') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Colors -->
                    <div
                        class="space-y-5 rounded-[2rem] border border-slate-100 bg-white p-5 shadow-sm transition-all duration-300 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <h4 class="mb-6 flex items-center text-sm font-black tracking-widest text-slate-400 uppercase">
                            <Palette class="me-3" :size="16" :style="{ color: 'var(--brand-primary)' }" /> {{ __('Brand Colors') }}
                        </h4>
                        <div class="space-y-6">
                            <!-- Helper Text -->
                            <div class="rounded-xl border border-blue-100 bg-blue-50 p-3 dark:border-blue-900/30 dark:bg-blue-900/10">
                                <p class="flex items-start gap-2 text-[11px] text-blue-600 dark:text-blue-400">
                                    <Info :size="14" class="mt-0.5 shrink-0" />
                                    <span>{{
                                        __('Customize your admin panel theme. These colors affect navigation, buttons, and text contrast.')
                                    }}</span>
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Primary Palette -->
                                <div class="space-y-4">
                                    <h5
                                        class="border-b border-slate-100 pb-2 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase dark:border-slate-800"
                                    >
                                        {{ __('Primary Palette') }}
                                    </h5>

                                    <div class="space-y-3">
                                        <!-- Primary -->
                                        <div>
                                            <label class="mb-1 block text-start text-[10px] font-bold text-slate-500 dark:text-slate-400">{{
                                                __('Primary Color')
                                            }}</label>
                                            <div class="flex items-center gap-3">
                                                <input
                                                    v-model="form.primary"
                                                    type="color"
                                                    class="h-8 w-14 cursor-pointer rounded border-0 bg-transparent p-0"
                                                />
                                                <input
                                                    v-model="form.primary"
                                                    type="text"
                                                    class="w-full rounded border-slate-200 bg-slate-50 px-2 py-1.5 font-mono text-xs outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800"
                                                />
                                            </div>
                                        </div>
                                        <!-- Primary Light -->
                                        <div>
                                            <label class="mb-1 block text-start text-[10px] font-bold text-slate-500 dark:text-slate-400">{{
                                                __('Primary Light (soft bg)')
                                            }}</label>
                                            <div class="flex items-center gap-3">
                                                <input
                                                    v-model="form.primary_light"
                                                    type="color"
                                                    class="h-8 w-14 cursor-pointer rounded border-0 bg-transparent p-0"
                                                />
                                                <input
                                                    v-model="form.primary_light"
                                                    type="text"
                                                    class="w-full rounded border-slate-200 bg-slate-50 px-2 py-1.5 font-mono text-xs outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800"
                                                />
                                            </div>
                                        </div>
                                        <!-- Primary Dark Text -->
                                        <div>
                                            <label class="mb-1 block text-start text-[10px] font-bold text-slate-500 dark:text-slate-400">{{
                                                __('Primary Dark Text (for dark mode)')
                                            }}</label>
                                            <div class="flex items-center gap-3">
                                                <input
                                                    v-model="form.primary_dark_text"
                                                    type="color"
                                                    class="h-8 w-14 cursor-pointer rounded border-0 bg-transparent p-0"
                                                />
                                                <input
                                                    v-model="form.primary_dark_text"
                                                    type="text"
                                                    class="w-full rounded border-slate-200 bg-slate-50 px-2 py-1.5 font-mono text-xs outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Secondary & Sidebar -->
                                <div class="space-y-4">
                                    <h5
                                        class="border-b border-slate-100 pb-2 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase dark:border-slate-800"
                                    >
                                        {{ __('Secondary & Sidebar') }}
                                    </h5>

                                    <div class="space-y-3">
                                        <!-- Secondary -->
                                        <div>
                                            <label class="mb-1 block text-start text-[10px] font-bold text-slate-500 dark:text-slate-400">{{
                                                __('Secondary Color')
                                            }}</label>
                                            <div class="flex items-center gap-3">
                                                <input
                                                    v-model="form.secondary"
                                                    type="color"
                                                    class="h-8 w-14 cursor-pointer rounded border-0 bg-transparent p-0"
                                                />
                                                <input
                                                    v-model="form.secondary"
                                                    type="text"
                                                    class="w-full rounded border-slate-200 bg-slate-50 px-2 py-1.5 font-mono text-xs outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800"
                                                />
                                            </div>
                                        </div>

                                        <!-- Sidebar Rail -->
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="mb-1 block text-start text-[9px] font-bold text-slate-500 dark:text-slate-400">{{
                                                    __('Sidebar Rail BG')
                                                }}</label>
                                                <div class="flex items-center gap-2">
                                                    <input
                                                        v-model="form.sidebar_rail_bg"
                                                        type="color"
                                                        class="h-6 w-8 cursor-pointer rounded border-0 bg-transparent p-0"
                                                    />
                                                    <input
                                                        v-model="form.sidebar_rail_bg"
                                                        type="text"
                                                        class="w-full rounded border-slate-200 bg-slate-50 px-2 py-1 font-mono text-[10px] outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800"
                                                    />
                                                </div>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-start text-[9px] font-bold text-slate-500 dark:text-slate-400">{{
                                                    __('Rail BG (Dark)')
                                                }}</label>
                                                <div class="flex items-center gap-2">
                                                    <input
                                                        v-model="form.sidebar_rail_bg_dark"
                                                        type="color"
                                                        class="h-6 w-8 cursor-pointer rounded border-0 bg-transparent p-0"
                                                    />
                                                    <input
                                                        v-model="form.sidebar_rail_bg_dark"
                                                        type="text"
                                                        class="w-full rounded border-slate-200 bg-slate-50 px-2 py-1 font-mono text-[10px] outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Sidebar Icons -->
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="mb-1 block text-start text-[9px] font-bold text-slate-500 dark:text-slate-400">{{
                                                    __('Sidebar Icon')
                                                }}</label>
                                                <div class="flex items-center gap-2">
                                                    <input
                                                        v-model="form.sidebar_icon_color"
                                                        type="color"
                                                        class="h-6 w-8 cursor-pointer rounded border-0 bg-transparent p-0"
                                                    />
                                                    <input
                                                        v-model="form.sidebar_icon_color"
                                                        type="text"
                                                        class="w-full rounded border-slate-200 bg-slate-50 px-2 py-1 font-mono text-[10px] outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800"
                                                    />
                                                </div>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-start text-[9px] font-bold text-slate-500 dark:text-slate-400">{{
                                                    __('Icon (Dark)')
                                                }}</label>
                                                <div class="flex items-center gap-2">
                                                    <input
                                                        v-model="form.sidebar_icon_color_dark"
                                                        type="color"
                                                        class="h-6 w-8 cursor-pointer rounded border-0 bg-transparent p-0"
                                                    />
                                                    <input
                                                        v-model="form.sidebar_icon_color_dark"
                                                        type="text"
                                                        class="w-full rounded border-slate-200 bg-slate-50 px-2 py-1 font-mono text-[10px] outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. CONTACT & SOCIAL -->
                <div
                    class="rounded-[2rem] border border-slate-100 bg-white p-5 shadow-sm transition-all duration-300 dark:border-slate-800 dark:bg-slate-900"
                >
                    <h4 class="mb-8 flex items-center text-start text-base font-black text-slate-800 dark:text-white">
                        <Briefcase class="me-4" :size="20" :style="{ color: 'var(--brand-primary)' }" />
                        <span>{{ __('Contact & Connectivity') }}</span>
                    </h4>

                    <div class="grid grid-cols-1 gap-10 md:grid-cols-2">
                        <div class="space-y-6">
                            <h5
                                class="border-b border-slate-100 pb-2 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase dark:border-slate-800"
                            >
                                {{ __('Business Details') }}
                            </h5>
                            <div class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                                        <Mail :size="16" />
                                    </div>
                                    <input
                                        v-model="form.contact_email"
                                        type="email"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 font-medium text-slate-900 transition-all outline-none focus:opacity-100 focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        :style="{ '--tw-ring-color': 'var(--brand-primary)' }"
                                    />
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                                        <Phone :size="16" />
                                    </div>
                                    <input
                                        v-model="form.contact_phone"
                                        type="text"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 font-medium text-slate-900 transition-all outline-none focus:opacity-100 focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        :style="{ '--tw-ring-color': 'var(--brand-primary)' }"
                                    />
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                                        <MapPin :size="16" />
                                    </div>
                                    <input
                                        v-model="form.contact_address"
                                        type="text"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 font-medium text-slate-900 transition-all outline-none focus:opacity-100 focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        :style="{ '--tw-ring-color': 'var(--brand-primary)' }"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <h5
                                class="border-b border-slate-100 pb-2 text-start text-[10px] font-bold tracking-widest text-slate-400 uppercase dark:border-slate-800"
                            >
                                {{ __('Social Presence') }}
                            </h5>
                            <div class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/20"
                                        :style="{
                                            color: 'var(--brand-primary)',
                                            backgroundColor: 'color-mix(in srgb, var(--brand-primary), transparent 90%)',
                                        }"
                                    >
                                        <Facebook :size="16" />
                                    </div>
                                    <input
                                        v-model="form.social_facebook"
                                        type="text"
                                        :placeholder="__('Facebook Profile URL')"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-medium text-slate-900 transition-all outline-none focus:opacity-100 focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        :style="{ '--tw-ring-color': 'var(--brand-primary)' }"
                                    />
                                </div>
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 dark:bg-sky-900/20"
                                        :style="{
                                            color: 'var(--brand-primary)',
                                            backgroundColor: 'color-mix(in srgb, var(--brand-primary), transparent 90%)',
                                        }"
                                    >
                                        <Twitter :size="16" />
                                    </div>
                                    <input
                                        v-model="form.social_twitter"
                                        type="text"
                                        :placeholder="__('Twitter Profile URL')"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-medium text-slate-900 transition-all outline-none focus:opacity-100 focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        :style="{ '--tw-ring-color': 'var(--brand-primary)' }"
                                    />
                                </div>
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-pink-50 dark:bg-pink-900/20"
                                        :style="{
                                            color: 'var(--brand-primary)',
                                            backgroundColor: 'color-mix(in srgb, var(--brand-primary), transparent 90%)',
                                        }"
                                    >
                                        <Instagram :size="16" />
                                    </div>
                                    <input
                                        v-model="form.social_instagram"
                                        type="text"
                                        :placeholder="__('Instagram Profile URL')"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-medium text-slate-900 transition-all outline-none focus:opacity-100 focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        :style="{ '--tw-ring-color': 'var(--brand-primary)' }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
