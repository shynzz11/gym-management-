import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';

type InertiaPageWithName = {
    props?: {
        name?: unknown;
        branding?: {
            business_settings?: {
                business_name?: unknown;
            };
        };
    };
};

const fallbackAppName = import.meta.env.VITE_APP_NAME || 'Laravel';

const resolveAppName = (candidate: unknown): string | null => {
    return typeof candidate === 'string' && candidate.trim().length > 0 ? candidate.trim() : null;
};

const resolveAppNameFromPage = (page?: InertiaPageWithName): string | null => {
    const businessName = resolveAppName(page?.props?.branding?.business_settings?.business_name);
    if (businessName) {
        return businessName;
    }

    return resolveAppName(page?.props?.name);
};

const getInitialPageName = (): string | null => {
    const appElement = document.getElementById('app');
    const pagePayload = appElement?.dataset.page;

    if (!pagePayload) {
        return null;
    }

    try {
        const page = JSON.parse(pagePayload) as InertiaPageWithName;

        return resolveAppNameFromPage(page);
    } catch {
        return null;
    }
};

let appName = getInitialPageName() || fallbackAppName;

import { wTrans } from './Core/i18n';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const initialName = resolveAppNameFromPage(props.initialPage as InertiaPageWithName | undefined);
        if (initialName) {
            appName = initialName;
        }

        router.on('navigate', (event) => {
            const nextName = resolveAppNameFromPage(event.detail.page as InertiaPageWithName | undefined);
            if (nextName) {
                appName = nextName;
            }
        });

        const app = createApp({ render: () => h(App, props) });

        app.config.globalProperties.__ = wTrans;
        app.config.globalProperties.trans = wTrans;

        app.use(plugin).mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
