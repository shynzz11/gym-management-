export * from './auth';

import type { Auth } from './auth';

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    auth: Auth;
    branding: Record<string, any>;
    [key: string]: unknown;
};
