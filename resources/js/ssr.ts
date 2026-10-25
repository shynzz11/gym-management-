import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';

type InertiaPageProps = {
    name?: unknown;
    branding?: {
        business_settings?: {
            business_name?: unknown;
        };
    };
};

const resolveAppName = (candidate: unknown): string | null => {
    return typeof candidate === 'string' && candidate.trim().length > 0 ? candidate.trim() : null;
};

const resolveAppNameFromProps = (props: InertiaPageProps): string | null => {
    const businessName = resolveAppName(props.branding?.business_settings?.business_name);
    if (businessName) {
        return businessName;
    }

    return resolveAppName(props.name);
};

createServer(
    (page) =>
        createInertiaApp({
            page,
            render: renderToString,
            title: (title) => {
                const appName = resolveAppNameFromProps(page.props as InertiaPageProps)
                    || import.meta.env.VITE_APP_NAME
                    || 'Laravel';

                return title ? `${title} - ${appName}` : appName;
            },
            resolve: resolvePage,
            setup: ({ App, props, plugin }) => createSSRApp({ render: () => h(App, props) }).use(plugin),
        }),
    { cluster: true },
);

function resolvePage(name: string) {
    const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue');

    return resolvePageComponent<DefineComponent>(`./pages/${name}.vue`, pages);
}
