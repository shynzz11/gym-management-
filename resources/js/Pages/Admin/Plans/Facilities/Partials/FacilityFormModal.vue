<script setup lang="ts">
import { store, update } from '@/actions/App/Http/Controllers/Admin/PlanFacilityController';
import admin from '@/routes/admin';
import { router, useForm } from '@inertiajs/vue3';
import { PropType, watch } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    show: Boolean,
    facility: Object as PropType<any>,
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    description: '',
});

const resetFormState = (): void => {
    form.reset();
    form.clearErrors();
};

const syncFormState = (): void => {
    if (props.facility) {
        form.name = props.facility.name;
        form.description = props.facility.description || '';

        return;
    }

    resetFormState();
};

const closeModal = (): void => {
    resetFormState();
    emit('close');
};

watch(
    () => props.show,
    (show) => {
        if (show) {
            syncFormState();
        }
    },
    { immediate: true },
);

watch(
    () => props.facility,
    () => {
        if (props.show) {
            syncFormState();
        }
    },
);

const submit = (): void => {
    const onSuccess = (): void => {
        closeModal();

        router.get(
            admin.plans.facilities.index.url(),
            {},
            {
                replace: true,
                preserveScroll: true,
            },
        );
    };

    if (props.facility) {
        form.put(update.url(props.facility.id), {
            onSuccess,
        });

        return;
    }

    form.post(store.url(), {
        onSuccess,
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[160] flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

        <div
            class="relative w-full max-w-lg overflow-hidden rounded-[2.5rem] border border-slate-200 bg-white shadow-2xl transition-all dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="flex items-center justify-between border-b border-slate-100 px-8 py-6 dark:border-slate-800">
                <div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                        {{ facility ? __('Edit Facility') : __('Create Facility') }}
                    </h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ facility ? __('Update this facility for plan assignments.') : __('Add a new facility for plan assignments.') }}
                    </p>
                </div>
                <button
                    @click="closeModal"
                    class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:bg-slate-800 dark:text-slate-500 dark:hover:bg-slate-700 dark:hover:text-slate-300"
                >
                    <X :size="20" />
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-6 p-8">
                <div>
                    <label class="mb-2 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Facility Name') }}</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="h-11 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 text-sm font-medium transition-all focus:border-primary-500 focus:bg-white focus:ring-4 focus:ring-primary-500/10 dark:border-slate-800 dark:bg-slate-800/50 dark:text-white"
                        :placeholder="__('e.g. Steam Room')"
                    />
                    <p v-if="form.errors.name" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Details') }}</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium transition-all focus:border-primary-500 focus:bg-white focus:ring-4 focus:ring-primary-500/10 dark:border-slate-800 dark:bg-slate-800/50 dark:text-white"
                        :placeholder="__('Optional note for this facility')"
                    ></textarea>
                    <p v-if="form.errors.description" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.description }}</p>
                </div>

                <div class="mt-8 flex justify-end gap-3 border-t border-slate-100 pt-6 dark:border-slate-800">
                    <button
                        type="button"
                        @click="closeModal"
                        class="inline-flex h-11 items-center rounded-2xl bg-slate-50 px-6 text-sm font-bold text-slate-600 transition-colors hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex h-11 items-center justify-center rounded-2xl bg-primary-600 px-8 text-sm font-bold text-white shadow-xl shadow-primary-500/20 transition-all hover:bg-primary-700 active:scale-95 disabled:opacity-50"
                    >
                        {{ form.processing ? __('Processing...') : facility ? __('Save Changes') : __('Create Facility') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
