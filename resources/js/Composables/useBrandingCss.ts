import { onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useBrandingCss() {
    const page = usePage();

    const apply = () => {
        const settings = (page.props.branding as any)?.business_settings ?? {};
        const root = document.documentElement;

        const set = (prop: string, value: string | undefined | null, fallback: string) => {
            root.style.setProperty(prop, value || fallback);
        };

        // Primary brand color
        set('--brand-primary', settings.primary, '#315df5');

        // Sidebar rail (the narrow icon strip)
        set('--admin-sidebar-rail-bg', settings.sidebar_rail_bg, '#1e3a8a');
        set('--admin-sidebar-rail-bg-dark', settings.sidebar_rail_bg_dark, '#172554');

        // Main sidebar panel background
        set('--admin-sidebar-bg', settings.admin_sidebar_bg, '#ffffff');
        set('--admin-sidebar-bg-dark', settings.admin_sidebar_bg_dark, '#0f172a');

        // Sidebar borders
        set('--admin-sidebar-border', settings.admin_sidebar_border, '#e2e8f0');
        set('--admin-sidebar-border-dark', settings.admin_sidebar_border_dark, '#1e293b');

        // Sidebar icon colors
        set('--admin-sidebar-icon', settings.sidebar_icon_color, '#ffffff');
        set('--admin-sidebar-icon-dark', settings.sidebar_icon_color_dark, '#ffffff');

        // Active nav item
        set('--admin-active-item-bg', settings.primary_light, '#eff6ff');
        set('--admin-active-item-text-dark', settings.primary_dark_text, '#93c5fd');
    };

    onMounted(apply);
    watch(() => (page.props.branding as any)?.business_settings, apply, { deep: true });
}
