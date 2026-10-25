<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Mail, Shield, Server, User, Key, Globe, Send, Save } from 'lucide-vue-next';
import { test as sendTest, update } from '@/routes/admin/business/email';
import { ref } from 'vue';

interface Props {
    settings: {
        MAIL_HOST?: string;
        MAIL_PORT?: string;
        MAIL_USERNAME?: string;
        MAIL_PASSWORD?: string;
        MAIL_ENCRYPTION?: string;
        MAIL_FROM_ADDRESS?: string;
        MAIL_FROM_NAME?: string;
        test_email?: string;
    };
}

const props = defineProps<Props>();
const isTestModalOpen = ref(false);

const form = useForm({
    MAIL_HOST: props.settings.MAIL_HOST || '',
    MAIL_PORT: props.settings.MAIL_PORT || '',
    MAIL_USERNAME: props.settings.MAIL_USERNAME || '',
    MAIL_PASSWORD: props.settings.MAIL_PASSWORD || '',
    MAIL_ENCRYPTION: props.settings.MAIL_ENCRYPTION || 'tls',
    MAIL_FROM_ADDRESS: props.settings.MAIL_FROM_ADDRESS || '',
    MAIL_FROM_NAME: props.settings.MAIL_FROM_NAME || '',
    test_email: props.settings.test_email || props.settings.MAIL_FROM_ADDRESS || '',
});

const submit = () => {
    form.post(update.url(), {
        preserveScroll: true,
    });
};

const sendTestEmail = () => {
    form.post(sendTest.url(), {
        preserveScroll: true,
        onSuccess: () => {
            isTestModalOpen.value = false;
            form.clearErrors('test_email');
        },
    });
};

const openTestMailModal = () => {
    form.clearErrors('test_email');

    if (!form.test_email) {
        form.test_email = form.MAIL_FROM_ADDRESS || '';
    }

    isTestModalOpen.value = true;
};

const closeTestMailModal = () => {
    isTestModalOpen.value = false;
    form.clearErrors('test_email');
};
</script>

<template>
    <AdminLayout>
        <div class="mx-auto max-w-4xl">
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <div class="mb-2 flex items-center gap-3">
                        <div class="rounded-lg bg-orange-50 p-2 dark:bg-orange-900/20">
                            <Mail class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ __('Email Settings') }}
                        </h1>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ __("Configure your organization's SMTP credentials for automated notifications.") }}
                    </p>
                </div>

                <button
                    type="button"
                    @click="openTestMailModal"
                    :disabled="$page.props.is_demo_mode as boolean"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-600 px-5 py-2.5 font-medium text-white transition-colors hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <Send class="h-4 w-4" />
                    {{ __('Send Test Email') }}
                </button>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 bg-gray-50/50 p-6 dark:border-gray-700 dark:bg-gray-800/50">
                    <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
                        <Server class="h-5 w-5 text-gray-500" />
                        {{ __('SMTP Configuration') }}
                    </h3>
                </div>

                <form @submit.prevent="submit" class="space-y-6 p-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                <Globe class="h-4 w-4" />
                                {{ __('SMTP Host') }}
                            </label>
                            <input
                                v-model="form.MAIL_HOST"
                                type="text"
                                placeholder="smtp.mailtrap.io"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                :class="{ 'border-red-500': form.errors.MAIL_HOST }"
                            />
                            <p v-if="form.errors.MAIL_HOST" class="text-xs text-red-500">{{ form.errors.MAIL_HOST }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                <Shield class="h-4 w-4" />
                                {{ __('SMTP Port') }}
                            </label>
                            <input
                                v-model="form.MAIL_PORT"
                                type="text"
                                placeholder="587"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                :class="{ 'border-red-500': form.errors.MAIL_PORT }"
                            />
                            <p v-if="form.errors.MAIL_PORT" class="text-xs text-red-500">{{ form.errors.MAIL_PORT }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                <User class="h-4 w-4" />
                                {{ __('SMTP Username') }}
                            </label>
                            <input
                                v-model="form.MAIL_USERNAME"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                :class="{ 'border-red-500': form.errors.MAIL_USERNAME }"
                            />
                            <p v-if="form.errors.MAIL_USERNAME" class="text-xs text-red-500">{{ form.errors.MAIL_USERNAME }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                <Key class="h-4 w-4" />
                                {{ __('SMTP Password') }}
                            </label>
                             <input
                                :value="$page.props.is_demo_mode ? '******' : form.MAIL_PASSWORD"
                                @input="form.MAIL_PASSWORD = ($event.target as HTMLInputElement).value"
                                :disabled="$page.props.is_demo_mode as boolean"
                                type="password"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                :class="{ 'border-red-500': form.errors.MAIL_PASSWORD }"
                            />
                            <p v-if="form.errors.MAIL_PASSWORD" class="text-xs text-red-500">{{ form.errors.MAIL_PASSWORD }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                <Shield class="h-4 w-4" />
                                {{ __('Encryption') }}
                            </label>
                            <select
                                v-model="form.MAIL_ENCRYPTION"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-6 dark:border-gray-700">
                        <h3 class="mb-6 flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
                            <Send class="h-5 w-5 text-gray-500" />
                            {{ __('Sender Information') }}
                        </h3>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('From Email Address') }}
                                </label>
                                <input
                                    v-model="form.MAIL_FROM_ADDRESS"
                                    type="email"
                                    placeholder="noreply@gymos.com"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    :class="{ 'border-red-500': form.errors.MAIL_FROM_ADDRESS }"
                                />
                                <p v-if="form.errors.MAIL_FROM_ADDRESS" class="text-xs text-red-500">{{ form.errors.MAIL_FROM_ADDRESS }}</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('From Name') }}
                                </label>
                                <input
                                    v-model="form.MAIL_FROM_NAME"
                                    type="text"
                                    placeholder="GymOS Notifier"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    :class="{ 'border-red-500': form.errors.MAIL_FROM_NAME }"
                                />
                                <p v-if="form.errors.MAIL_FROM_NAME" class="text-xs text-red-500">{{ form.errors.MAIL_FROM_NAME }}</p>
                            </div>
                        </div>
                    </div>

                     <div class="flex justify-end border-t border-gray-200 pt-4 dark:border-gray-700">
                         <button
                            type="submit"
                            :disabled="form.processing || ($page.props.is_demo_mode as boolean)"
                            class="flex items-center gap-2 rounded-lg bg-orange-600 px-6 py-2.5 font-medium text-white shadow-lg shadow-orange-500/30 transition-colors hover:bg-orange-700 disabled:bg-gray-400 disabled:shadow-none"
                        >
                            <Save class="h-4 w-4" :class="{ 'animate-spin': form.processing }" />
                            {{ form.processing ? __('Saving...') : ($page.props.is_demo_mode as boolean) ? __('Save Disabled') : __('Save Settings') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="isTestModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @click.self="closeTestMailModal">
            <div class="w-full max-w-2xl rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <h3 class="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-white">
                        <Send class="h-6 w-6 text-slate-500" />
                        {{ __('Test Mail Delivery') }}
                    </h3>
                    <p class="mt-3 text-gray-600 dark:text-gray-400">
                        {{ __('A test email will be sent using the SMTP configuration above.') }}
                    </p>
                </div>

                <div class="space-y-4 p-6">
                    <input
                        v-model="form.test_email"
                        type="email"
                        placeholder="admin@example.com"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-lg focus:ring-2 focus:ring-slate-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        :class="{ 'border-red-500': form.errors.test_email }"
                    />
                    <p v-if="form.errors.test_email" class="text-sm text-red-500">{{ form.errors.test_email }}</p>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 p-6 dark:border-gray-700">
                    <button
                        type="button"
                        @click="closeTestMailModal"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="button"
                        :disabled="form.processing"
                        @click="sendTestEmail"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-600 px-6 py-2.5 font-medium text-white transition-colors hover:bg-slate-700 disabled:bg-gray-400"
                    >
                        <Send class="h-4 w-4" />
                        {{ __('Send Test Email') }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
