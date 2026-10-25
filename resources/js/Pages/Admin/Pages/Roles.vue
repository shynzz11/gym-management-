<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Shared/Card.vue';

defineProps<{
    roles: Object;
}>();
</script>

<template>
    <AdminLayout>
        <template #header> Roles Management </template>

        <Card title="Roles List">
            <ul>
                <li v-for="role in roles" :key="role.id" class="border-b py-2">{{ role.name }} ({{ role.slug }})</li>
            </ul>
        </Card>
    </AdminLayout>
</template>
