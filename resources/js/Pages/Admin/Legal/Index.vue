<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { FileText, Save, Scale } from 'lucide-vue-next';
import Toast from '@/Components/Common/Toast.vue';
import RichTextEditor from '@/Components/Common/RichTextEditor.vue';

import { update } from '@/routes/admin/legal';

const props = defineProps<{
    settings: Record<string, string>;
}>();

const tabs = [
    { id: 'privacy_policy', label: 'Privacy Policy' },
    { id: 'terms_and_conditions', label: 'Terms & Conditions' },
    { id: 'about_us', label: 'About Us' },
];

const activeTab = ref(tabs[0].id);

const form = useForm({
    privacy_policy: props.settings.privacy_policy || '',
    terms_and_conditions: props.settings.terms_and_conditions || '',
    about_us: props.settings.about_us || '',
});

const submit = () => {
    form.post(update.url(), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="__('Legal Center')" />
    <AdminLayout>
        <Toast />
        <div class="animate-fade-in mx-auto w-full max-w-5xl space-y-6 p-4 lg:p-0">
            <!-- Header -->
            <div class="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ __('Legal Center') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Manage legal documents and policies.') }}</p>
                </div>
            </div>

            <!-- Content -->
            <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex min-h-[600px] flex-col lg:flex-row">
                    <!-- Sidebar Tabs -->
                    <div class="w-full space-y-2 border-e border-slate-200 bg-slate-50/50 p-4 lg:w-64 dark:border-slate-800 dark:bg-slate-800/30">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            class="flex w-full items-center gap-3 rounded-xl px-4 py-2 text-start text-sm font-bold transition-all"
                            :class="
                                activeTab === tab.id
                                    ? 'bg-white text-brand-600 shadow-sm ring-1 ring-slate-200 dark:bg-slate-800 dark:text-brand-400 dark:ring-slate-700'
                                    : 'text-slate-500 hover:bg-white/50 dark:hover:bg-slate-800/50'
                            "
                        >
                            <Scale :size="16" v-if="tab.id.includes('policy')" />
                            <FileText :size="16" v-else />
                            {{ __(tab.label) }}
                        </button>
                    </div>

                    <!-- Editor Area -->
                    <div class="flex-1 p-4 lg:p-5">
                        <div v-show="activeTab === 'privacy_policy'" class="flex h-full flex-col">
                            <h3 class="mb-4 text-base font-bold text-slate-900 dark:text-white">{{ __('Privacy Policy') }}</h3>
                            <div class="flex-1">
                                <RichTextEditor v-model="form.privacy_policy" />
                            </div>
                            <div
                                class="sticky bottom-0 z-10 -mx-6 mt-4 -mb-6 flex justify-end rounded-b-2xl border-t border-slate-100 bg-white/90 p-4 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/90"
                            >
                                <button
                                    @click="submit"
                                    :disabled="form.processing"
                                    class="flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white shadow-lg transition-all hover:opacity-90 dark:bg-brand-500"
                                >
                                    <Save :size="18" />
                                    {{ form.processing ? __('Saving...') : __('Save Changes') }}
                                </button>
                            </div>
                        </div>

                        <div v-show="activeTab === 'terms_and_conditions'" class="flex h-full flex-col">
                            <h3 class="mb-4 text-base font-bold text-slate-900 dark:text-white">{{ __('Terms & Conditions') }}</h3>
                            <div class="flex-1">
                                <RichTextEditor v-model="form.terms_and_conditions" />
                            </div>
                            <div
                                class="sticky bottom-0 z-10 -mx-6 mt-4 -mb-6 flex justify-end rounded-b-2xl border-t border-slate-100 bg-white/90 p-4 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/90"
                            >
                                <button
                                    @click="submit"
                                    :disabled="form.processing"
                                    class="flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white shadow-lg transition-all hover:opacity-90 dark:bg-brand-500"
                                >
                                    <Save :size="18" />
                                    {{ form.processing ? __('Saving...') : __('Save Changes') }}
                                </button>
                            </div>
                        </div>

                        <div v-show="activeTab === 'about_us'" class="flex h-full flex-col">
                            <h3 class="mb-4 text-base font-bold text-slate-900 dark:text-white">{{ __('About Us') }}</h3>
                            <div class="flex-1">
                                <RichTextEditor v-model="form.about_us" />
                            </div>
                            <div
                                class="sticky bottom-0 z-10 -mx-6 mt-4 -mb-6 flex justify-end rounded-b-2xl border-t border-slate-100 bg-white/90 p-4 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/90"
                            >
                                <button
                                    @click="submit"
                                    :disabled="form.processing"
                                    class="flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white shadow-lg transition-all hover:opacity-90 dark:bg-brand-500"
                                >
                                    <Save :size="18" />
                                    {{ form.processing ? __('Saving...') : __('Save Changes') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
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
</style>
