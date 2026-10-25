<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

interface Toast {
    id: string;
    message: string;
    type: 'success' | 'error' | 'warning';
}

const page = usePage();
const toasts = ref<Toast[]>([]);

const addToast = (message: string, type: 'success' | 'error' | 'warning') => {
    const id = Date.now().toString() + Math.random();
    toasts.value.push({ id, message, type });

    setTimeout(() => removeToast(id), 5000);
};

const removeToast = (id: string) => {
    const index = toasts.value.findIndex((t) => t.id === id);
    if (index > -1) {
        toasts.value.splice(index, 1);
    }
};

watch(
    () => page.props.flash,
    (flash: any) => {
        if (flash?.success) {
            addToast(flash.success, 'success');
        }
        if (flash?.error) {
            addToast(flash.error, 'error');
        }
        if (flash?.warning) {
            addToast(flash.warning, 'warning');
        }
        // Handle error bags if needed, but usually those are inline.
        // Sometimes 'message' is used for generic success/error but let's stick to these 3.
    },
    { deep: true },
);

onMounted(() => {
    // Check if there are initial flash messages (e.g. from redirect)
    const flash = page.props.flash as any;
    if (flash?.success) addToast(flash.success, 'success');
    if (flash?.error) addToast(flash.error, 'error');
    if (flash?.warning) addToast(flash.warning, 'warning');
});
</script>

<template>
    <div class="pointer-events-none fixed top-24 right-6 z-50 flex flex-col space-y-4">
        <TransitionGroup name="toast">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="group pointer-events-auto relative max-w-md min-w-[300px] transform overflow-hidden rounded-xl border-l-[6px] bg-white shadow-xl transition-all duration-300 dark:bg-slate-900"
                :class="{
                    'border-[var(--toast-success)]': toast.type === 'success',
                    'border-[var(--toast-error)]': toast.type === 'error',
                    'border-[var(--toast-warning)]': toast.type === 'warning',
                }"
            >
                <div class="flex items-start space-x-3 p-4">
                    <!-- Icons -->
                    <div class="flex-shrink-0">
                        <svg
                            v-if="toast.type === 'success'"
                            class="h-6 w-6 text-[var(--toast-success)]"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg
                            v-if="toast.type === 'error'"
                            class="h-6 w-6 text-[var(--toast-error)]"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <svg
                            v-if="toast.type === 'warning'"
                            class="h-6 w-6 text-[var(--toast-warning)]"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 pt-0.5">
                        <h3
                            class="mb-1 text-sm font-bold capitalize"
                            :class="{
                                'text-[var(--toast-success)]': toast.type === 'success',
                                'text-[var(--toast-error)]': toast.type === 'error',
                                'text-[var(--toast-warning)]': toast.type === 'warning',
                            }"
                        >
                            {{ toast.type }}
                        </h3>
                        <p class="text-sm leading-relaxed font-medium text-slate-600 dark:text-slate-300">
                            {{ toast.message }}
                        </p>
                    </div>

                    <!-- Close Button -->
                    <button @click="removeToast(toast.id)" class="text-slate-400 transition-colors hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Progress Bar -->
                <div
                    class="animate-wiggle absolute bottom-0 left-0 h-1 w-full bg-current opacity-20"
                    :class="{
                        'text-[var(--toast-success)]': toast.type === 'success',
                        'text-[var(--toast-error)]': toast.type === 'error',
                        'text-[var(--toast-warning)]': toast.type === 'warning',
                    }"
                    style="animation: progress 5s linear forwards"
                ></div>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.4s cubic-bezier(0.5, 0, 0, 1.25);
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(100px) scale(0.9);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(100px) scale(0.9);
}

@keyframes progress {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}
</style>
