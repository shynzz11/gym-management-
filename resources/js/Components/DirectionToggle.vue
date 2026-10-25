<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { ArrowLeftRight } from 'lucide-vue-next';

interface Props {
    showLabel?: boolean;
    className?: string;
}

const props = withDefaults(defineProps<Props>(), {
    showLabel: true,
    className: '',
});

const direction = ref(localStorage.getItem('direction') || 'ltr');

const toggleDirection = () => {
    direction.value = direction.value === 'ltr' ? 'rtl' : 'ltr';
    localStorage.setItem('direction', direction.value);
    document.documentElement.setAttribute('dir', direction.value);
};

onMounted(() => {
    document.documentElement.setAttribute('dir', direction.value);
});
</script>

<template>
    <button
        @click="toggleDirection"
        :class="[
            'flex items-center rounded-xl border border-slate-200 bg-white text-xs font-bold whitespace-nowrap text-slate-700 shadow-sm transition-all hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700',
            showLabel ? 'p-1.5 sm:px-3 sm:py-1.5' : 'p-1.5',
            className,
        ]"
        :title="__('Toggle Direction')"
    >
        <ArrowLeftRight :size="16" :class="{ 'sm:me-2': showLabel }" />
        <span v-if="showLabel" class="hidden sm:inline">{{ direction.toUpperCase() }}</span>
    </button>
</template>
