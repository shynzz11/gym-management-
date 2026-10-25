<script setup lang="ts">
defineProps<{
    title?: string;
}>();
</script>

<template>
    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
        <div v-if="title" class="border-b border-gray-200 bg-gray-50 px-6 py-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900">{{ title }}</h3>
        </div>
        <div class="border-b border-gray-200 bg-white p-6">
            <slot />
        </div>
    </div>
</template>
