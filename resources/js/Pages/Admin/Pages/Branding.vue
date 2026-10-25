<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Shared/Card.vue';
import Button from '@/Shared/Button.vue';

defineProps<{
    brand: Object;
}>();
</script>

<template>
    <AdminLayout>
        <template #header> Branding Settings </template>

        <Card title="Brand Configuration">
            <p>Customize your brand settings here.</p>
            <!-- Form placeholder -->
        </Card>
    </AdminLayout>
</template>
