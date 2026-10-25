<script setup lang="ts">
defineProps<{
    type?: 'button' | 'submit' | 'reset';
    variant?: 'primary' | 'secondary' | 'danger';
}>();
</script>

<template>
    <button
        :type="type || 'button'"
        class="rounded-md px-4 py-2 font-semibold text-white transition duration-150 ease-in-out"
        :class="{
            'bg-blue-600 hover:bg-blue-700': variant === 'primary' || !variant,
            'bg-gray-500 hover:bg-gray-600': variant === 'secondary',
            'bg-red-600 hover:bg-red-700': variant === 'danger',
        }"
    >
        <slot />
    </button>
</template>
