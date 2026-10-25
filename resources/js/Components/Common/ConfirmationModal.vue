<script setup lang="ts">
import { PropType } from 'vue';
import { AlertTriangle, AlertCircle, Info, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    show: Boolean,
    title: {
        type: String,
        default: 'Are you sure?',
    },
    message: {
        type: String,
        default: 'This action cannot be undone.',
    },
    confirmText: {
        type: String,
        default: 'Confirm',
    },
    cancelText: {
        type: String,
        default: 'Cancel',
    },
    type: {
        type: String as PropType<'danger' | 'warning' | 'info'>,
        default: 'danger',
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'confirm']);

const colors = {
    danger: {
        iconBg: 'bg-red-100 dark:bg-red-900/20',
        iconText: 'text-red-600 dark:text-red-400',
        button: 'bg-red-600 hover:bg-red-700 text-white shadow-red-600/20',
        icon: AlertTriangle,
    },
    warning: {
        iconBg: 'bg-amber-100 dark:bg-amber-900/20',
        iconText: 'text-amber-600 dark:text-amber-400',
        button: 'bg-amber-600 hover:bg-amber-700 text-white shadow-amber-600/20',
        icon: AlertCircle,
    },
    info: {
        iconBg: 'bg-blue-100 dark:bg-blue-900/20',
        iconText: 'text-blue-600 dark:text-blue-400',
        button: 'bg-blue-600 hover:bg-blue-700 text-white shadow-blue-600/20',
        icon: Info,
    },
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

        <div
            class="relative w-full transform overflow-hidden rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-900/5 transition-all sm:max-w-lg dark:bg-slate-900 dark:ring-white/10"
        >
            <div class="space-y-6 p-6 text-center">
                <!-- Icon -->
                <div
                    class="animate-bounce-short mx-auto flex h-16 w-16 items-center justify-center rounded-full"
                    :class="colors[type].iconBg + ' ' + colors[type].iconText"
                >
                    <component :is="colors[type].icon" :size="32" />
                </div>

                <!-- Text -->
                <div>
                    <h3 class="mb-2 text-xl font-bold text-slate-900 dark:text-white">{{ title }}</h3>
                    <p class="text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ message }}</p>
                </div>

                <!-- Actions -->
                <div class="flex flex-col gap-3 pt-2 sm:flex-row">
                    <button
                        @click="$emit('close')"
                        class="flex-1 rounded-xl border border-slate-200 px-6 py-3 text-sm font-bold text-slate-500 transition-all hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
                    >
                        {{ cancelText }}
                    </button>
                    <button
                        @click="$emit('confirm')"
                        :disabled="processing"
                        class="flex flex-1 items-center justify-center rounded-xl px-6 py-3 text-sm font-bold shadow-xl transition-all active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="colors[type].button"
                    >
                        <Loader2 v-if="processing" class="me-2 animate-spin" :size="16" />
                        {{ confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-bounce-short {
    animation: bounce 0.5s infinite alternate;
}
@keyframes bounce {
    from {
        transform: translateY(0);
    }
    to {
        transform: translateY(-5px);
    }
}
</style>
