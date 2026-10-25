<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { PropType, watch } from 'vue';
import { X, Timer, Sparkles } from 'lucide-vue-next';
import admin from '@/routes/admin';

const props = defineProps({
    show: Boolean,
    accessType: Object as PropType<any>,
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    slug: '',
    description: '',
    is_anytime: true,
    start_time: '06:00:00',
    end_time: '22:00:00',
    is_active: true,
});

watch(
    () => props.accessType,
    (type) => {
        if (type) {
            form.name = type.name;
            form.slug = type.slug;
            form.description = type.description || '';
            form.is_anytime = !!type.is_anytime;
            form.start_time = type.start_time || '06:00:00';
            form.end_time = type.end_time || '22:00:00';
            form.is_active = !!type.is_active;
        } else {
            form.reset();
        }
    },
    { immediate: true },
);

const normalizeTimeValue = (value: string | null): string | null => {
    if (!value) {
        return null;
    }

    return value.length === 5 ? `${value}:00` : value;
};

const submit = () => {
    const transformPayload = (data: any) => ({
        ...data,
        start_time: data.is_anytime ? null : normalizeTimeValue(data.start_time),
        end_time: data.is_anytime ? null : normalizeTimeValue(data.end_time),
    });

    if (props.accessType) {
        form.transform(transformPayload).put(admin.plans.accessTypes.update.url(props.accessType.id), {
            onSuccess: () => emit('close'),
        });
    } else {
        form.transform(transformPayload).post(admin.plans.accessTypes.store.url(), {
            onSuccess: () => emit('close'),
        });
    }
};

const generateSlug = () => {
    if (!props.accessType) {
        form.slug = form.name
            .toLowerCase()
            .replace(/ /g, '-')
            .replace(/[^\w-]+/g, '');
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[160] overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm sm:p-6" @click.self="emit('close')">
        <div class="flex min-h-full items-center justify-center">
            <div
                class="relative flex w-full max-w-lg flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl transition-all max-sm:max-h-[calc(100vh-2rem)] sm:max-h-[90vh] dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="shrink-0 border-b border-slate-100 px-6 py-5 sm:px-8 sm:py-6 dark:border-slate-800">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                                {{ accessType ? __('Edit Plan Access Type') : __('Create Plan Access Type') }}
                            </h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                {{
                                    accessType
                                        ? __('Update this plan access type configuration.')
                                        : __('Define a new access type for membership plans.')
                                }}
                            </p>
                        </div>
                        <button
                            @click="emit('close')"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:bg-slate-800 dark:text-slate-500 dark:hover:bg-slate-700 dark:hover:text-slate-300"
                        >
                            <X :size="20" />
                        </button>
                    </div>
                </div>

                <form @submit.prevent="submit" class="flex min-h-0 flex-1 flex-col">
                    <div class="custom-scrollbar min-h-0 flex-1 space-y-6 overflow-y-auto p-6 sm:p-8">
                        <div>
                            <label class="mb-2 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Access Type Name') }}</label>
                            <input
                                v-model="form.name"
                                @input="generateSlug"
                                type="text"
                                required
                                class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium transition-all focus:border-primary-500 focus:bg-white focus:ring-4 focus:ring-primary-500/10 dark:border-slate-800 dark:bg-slate-800/50 dark:text-white dark:focus:border-primary-500"
                                placeholder="e.g. Early Bird Only"
                            />
                            <p v-if="form.errors.name" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{
                                __('Slug (Unique Identifier)')
                            }}</label>
                            <input
                                v-model="form.slug"
                                type="text"
                                required
                                :disabled="!!accessType"
                                class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium transition-all focus:border-primary-500 disabled:opacity-50 dark:border-slate-800 dark:bg-slate-800/50 dark:text-white"
                                placeholder="early-bird"
                            />
                            <p v-if="form.errors.slug" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.slug }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Description') }}</label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium transition-all focus:border-primary-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-white"
                                placeholder="Brief description for trainers..."
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-between rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50">
                            <div class="flex items-center gap-3">
                                <Sparkles :size="18" class="text-primary-500" />
                                <div>
                                    <span class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Unlimited 24/7 Access') }}</span>
                                    <p class="text-[10px] font-medium text-slate-500">{{ __('Disable to set specific time restrictions.') }}</p>
                                </div>
                            </div>
                            <label class="relative cursor-pointer">
                                <input type="checkbox" v-model="form.is_anytime" class="peer sr-only" />
                                <div class="h-6 w-11 rounded-full bg-slate-200 transition-colors peer-checked:bg-primary-500 dark:bg-slate-800"></div>
                                <div
                                    class="absolute top-1 left-1 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-5"
                                ></div>
                            </label>
                        </div>

                        <div v-if="!form.is_anytime" class="animate-in fade-in slide-in-from-top-2 grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-2 block flex items-center gap-2 text-xs font-bold tracking-widest text-slate-500 uppercase">
                                    <Timer :size="14" /> {{ __('Start Time') }}
                                </label>
                                <input
                                    v-model="form.start_time"
                                    type="time"
                                    step="1"
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium transition-all focus:border-primary-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-white"
                                />
                                <p v-if="form.errors.start_time" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.start_time }}</p>
                            </div>
                            <div>
                                <label class="mb-2 block flex items-center gap-2 text-xs font-bold tracking-widest text-slate-500 uppercase">
                                    <Timer :size="14" /> {{ __('End Time') }}
                                </label>
                                <input
                                    v-model="form.end_time"
                                    type="time"
                                    step="1"
                                    class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium transition-all focus:border-primary-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-white"
                                />
                                <p v-if="form.errors.end_time" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.end_time }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <label class="flex cursor-pointer items-center gap-3">
                                <div class="relative">
                                    <input type="checkbox" v-model="form.is_active" class="peer sr-only" />
                                    <div
                                        class="h-6 w-11 rounded-full bg-slate-200 transition-colors peer-checked:bg-emerald-500 dark:bg-slate-800"
                                    ></div>
                                    <div
                                        class="absolute top-1 left-1 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-5"
                                    ></div>
                                </div>
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('Active & Available') }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="shrink-0 border-t border-slate-100 px-6 py-5 sm:px-8 dark:border-slate-800">
                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                @click="emit('close')"
                                class="rounded-lg bg-slate-50 px-6 py-3 text-sm font-bold text-slate-600 transition-colors hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700"
                            >
                                {{ __('Cancel') }}
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex items-center justify-center rounded-lg bg-primary-600 px-8 py-3 text-sm font-bold text-white shadow-xl shadow-primary-500/20 transition-all hover:bg-primary-700 active:scale-95 disabled:opacity-50"
                            >
                                {{ form.processing ? __('Processing...') : accessType ? __('Save Changes') : __('Create Access Type') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
