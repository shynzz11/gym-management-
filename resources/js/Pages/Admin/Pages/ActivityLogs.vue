<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Shared/Card.vue';

defineProps<{
    logs: Object;
}>();
</script>

<template>
    <AdminLayout>
        <template #header> Activity Logs </template>

        <Card title="System Activity">
            <div v-if="logs.length === 0" class="text-gray-500">No activity logs found.</div>
            <ul v-else class="space-y-4">
                <li v-for="log in logs" :key="log.id" class="rounded bg-gray-50 p-3">
                    <span class="font-bold">{{ log.user?.name }}</span>
                    {{ log.action }} -
                    <span class="text-sm text-gray-500">{{ log.created_at }}</span>
                </li>
            </ul>
        </Card>
    </AdminLayout>
</template>
