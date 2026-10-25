<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    show: Boolean,
    title: {
        type: String,
        default: '',
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};

const maxWidthClass = computed(() => {
    return (
        {
            sm: 'sm:max-w-sm',
            md: 'sm:max-w-md',
            lg: 'sm:max-w-lg',
            xl: 'sm:max-w-xl',
            '2xl': 'sm:max-w-2xl',
        }[props.maxWidth] || 'sm:max-w-2xl'
    );
});
</script>

<template>
    <div v-show="show" class="fixed inset-0 z-[100] overflow-y-auto px-4 py-6 sm:px-0" scroll-region>
        <transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-show="show" class="fixed inset-0 transform transition-all" @click="close">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm dark:bg-slate-900/80"></div>
            </div>
        </transition>

        <transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <div
                v-show="show"
                class="mb-6 transform overflow-hidden rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-900/5 transition-all sm:mx-auto sm:w-full dark:bg-slate-900 dark:ring-white/10"
                :class="maxWidthClass"
            >
                <div v-if="title" class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ title }}
                    </h3>
                    <button
                        type="button"
                        class="text-gray-400 transition-colors hover:text-gray-500 focus:outline-none dark:hover:text-gray-300"
                        @click="close"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="px-6 py-4">
                    <slot />
                </div>

                <div v-if="$slots.footer" class="bg-gray-50 px-6 py-4 text-right dark:bg-gray-800/50">
                    <slot name="footer" />
                </div>
            </div>
        </transition>
    </div>
</template>
