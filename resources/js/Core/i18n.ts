import { usePage } from '@inertiajs/vue3';

export function trans(key: string, replacements: Record<string, string> = {}): string {
    const page = usePage();
    const translations = (page.props.translations as Record<string, string>) || {};

    let translation = translations[key] || key;

    Object.keys(replacements).forEach((replacement) => {
        translation = translation.replace(`:${replacement}`, replacements[replacement]);
    });

    return translation;
}

export const wTrans = trans;
