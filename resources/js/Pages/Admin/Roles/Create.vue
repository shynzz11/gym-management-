<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChevronRight, Fingerprint } from 'lucide-vue-next';
import type { PropType } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import admin from '@/routes/admin';

defineProps({
    permissions: Object as PropType<Record<string, any>>,
});

const form = useForm({
    name: '',
    description: '',
    permissions: [] as string[],
});

const togglePermission = (key: string) => {
    if (form.permissions.includes(key)) {
        form.permissions = form.permissions.filter((p) => p !== key);
    } else {
        form.permissions.push(key);
    }
};

const toggleModule = (moduleKey: string, subModules: any) => {
    const keys = Object.keys(subModules);
    const allSelected = keys.every((k) => form.permissions.includes(k));

    if (allSelected) {
        // Deselect all
        form.permissions = form.permissions.filter((p) => !keys.includes(p));
    } else {
        // Select all
        keys.forEach((k) => {
            if (!form.permissions.includes(k)) form.permissions.push(k);
        });
    }
};

const submit = () => {
    form.post(admin.roles.store.url(), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head :title="__('Create New Role')" />

    <AdminLayout>
        <div class="animate-fade-in mx-auto max-w-5xl space-y-6">
            <!-- Header -->
            <div
                class="flex flex-col items-start justify-between gap-6 border-b border-slate-200 pb-6 sm:flex-row sm:items-center dark:border-slate-800"
            >
                <div>
                    <div class="mb-2 flex items-center gap-2 text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        <Link :href="admin.roles.index.url()" class="hover:text-brand-500">{{ __('Access Roles') }}</Link>
                        <ChevronRight :size="8" class="opacity-40 rtl:rotate-180" />
                        <span class="text-brand-500">{{ __('New Role') }}</span>
                    </div>
                    <h2 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ __('Create Access Role') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Configure deeply nested permissions for specific modules.') }}
                    </p>
                </div>
                <div class="flex w-full items-center gap-3 sm:w-auto">
                    <Link
                        :href="admin.roles.index.url()"
                        class="flex-1 rounded-2xl border border-slate-200 px-4 py-2 text-center text-sm font-bold text-slate-500 transition-all hover:bg-slate-50 sm:flex-none dark:border-slate-800 dark:hover:bg-slate-800"
                    >
                        {{ __('Cancel') }}
                    </Link>
                    <button
                        @click="submit"
                        :disabled="form.processing"
                        class="flex-1 rounded-2xl bg-brand-600 px-4 py-2 text-sm font-bold text-white shadow-xl shadow-brand-600/20 transition-all hover:bg-brand-700 disabled:opacity-50 sm:flex-none"
                    >
                        {{ form.processing ? __('Saving...') : __('Save Role') }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Role Details -->
                <div class="space-y-6 lg:col-span-4">
                    <div class="rounded-[2rem] border border-slate-200/60 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <h3 class="mb-8 flex items-center text-sm font-black tracking-widest text-slate-400 uppercase">
                            <Fingerprint :size="16" class="me-3 text-brand-500" /> {{ __('Role Identity') }}
                        </h3>
                        <div class="space-y-6">
                            <div>
                                <label class="mb-2 block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('Role Name') }}</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    :placeholder="__('e.g. Access Controller')"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 font-medium text-slate-900 transition-all outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-900/50 dark:text-white dark:placeholder:text-slate-500"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('Short Description') }}</label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    :placeholder="__('e.g. Can manage all system settings')"
                                    class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 font-medium text-slate-900 transition-all outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-900/50 dark:text-white dark:placeholder:text-slate-500"
                                ></textarea>
                                <p v-if="form.errors.description" class="mt-1 text-xs text-red-500">{{ form.errors.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permission Matrix -->
                <div class="space-y-8 lg:col-span-8">
                    <div
                        v-for="(module, moduleKey) in permissions"
                        :key="moduleKey"
                        class="overflow-hidden rounded-[2rem] border border-slate-200/60 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
                    >
                        <div class="flex items-center justify-between border-b border-slate-100 p-5 dark:border-slate-700">
                            <div class="flex items-center gap-3">
                                <h3 class="text-sm font-black tracking-widest text-slate-700 uppercase dark:text-slate-300">
                                    {{ __(module.label) }}
                                </h3>
                            </div>
                            <button
                                @click="toggleModule(moduleKey as string, module.sub_modules)"
                                class="text-[10px] font-bold tracking-widest text-brand-600 uppercase hover:underline"
                            >
                                {{ __('Select Section All') }}
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                            <label
                                v-for="(sub, subKey) in module.sub_modules"
                                :key="subKey"
                                class="flex cursor-pointer items-center justify-between rounded-xl border border-slate-100 p-3 transition-all hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/50"
                            >
                                <div>
                                    <p class="text-xs font-bold text-slate-900 dark:text-white">{{ __(sub.label) }}</p>
                                    <p class="mt-0.5 text-[9px] text-slate-400">{{ __(sub.description) }}</p>
                                </div>
                                <div class="relative inline-block h-5 w-9">
                                    <input
                                        type="checkbox"
                                        :checked="form.permissions.includes(String(subKey))"
                                        @change="togglePermission(String(subKey))"
                                        class="peer sr-only"
                                    />
                                    <div
                                        class="peer h-6 w-11 rounded-full bg-slate-200 peer-checked:bg-brand-600 peer-focus:ring-4 peer-focus:ring-brand-300 peer-focus:outline-none after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white rtl:peer-checked:after:-translate-x-full dark:border-gray-600 dark:bg-slate-700 dark:peer-focus:ring-brand-800"
                                    ></div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
