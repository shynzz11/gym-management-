<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Eye, EyeOff, Landmark, ShieldCheck, WalletCards } from 'lucide-vue-next';
import Toast from '@/Components/Common/Toast.vue';
import { update as paymentSettingsUpdate } from '@/routes/admin/business/payment-settings';

interface Props {
    settings: {
        SSLCOMMERZ_ENABLED?: boolean;
        SSLCOMMERZ_STORE_ID?: string;
        SSLCOMMERZ_STORE_PASSWORD?: string;
        SSLCOMMERZ_SANDBOX?: boolean;
        STRIPE_ENABLED?: boolean;
        STRIPE_PUBLIC_KEY?: string;
        STRIPE_SECRET_KEY?: string;
    };
}

const props = defineProps<Props>();

const sslForm = useForm({
    SSLCOMMERZ_ENABLED: props.settings.SSLCOMMERZ_ENABLED ?? false,
    SSLCOMMERZ_STORE_ID: props.settings.SSLCOMMERZ_STORE_ID || '',
    SSLCOMMERZ_STORE_PASSWORD: props.settings.SSLCOMMERZ_STORE_PASSWORD || '',
    SSLCOMMERZ_SANDBOX: props.settings.SSLCOMMERZ_SANDBOX ?? true,
});

const stripeForm = useForm({
    STRIPE_ENABLED: props.settings.STRIPE_ENABLED ?? false,
    STRIPE_PUBLIC_KEY: props.settings.STRIPE_PUBLIC_KEY || '',
    STRIPE_SECRET_KEY: props.settings.STRIPE_SECRET_KEY || '',
});

const showSslStorePassword = ref(false);
const showStripeSecretKey = ref(false);

const saveSslcommerz = (): void => {
    sslForm.post(paymentSettingsUpdate.url(), {
        preserveScroll: true,
    });
};

const saveStripe = (): void => {
    stripeForm.post(paymentSettingsUpdate.url(), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout>
        <Toast />
        <div class="mx-auto max-w-5xl space-y-6">
            <div>
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ __('Payment Settings') }}</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Configure SSLCommerz and Stripe credentials used for checkout and callbacks.') }}
                    </p>
                </div>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700/60 dark:bg-slate-900">
                <form class="space-y-6" autocomplete="off" @submit.prevent="saveSslcommerz">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <h2 class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white">
                            <Landmark class="h-4 w-4 text-emerald-600" /> SSLCommerz
                        </h2>
                        <button
                            type="button"
                            class="inline-flex h-9 items-center rounded-lg border px-3 text-xs font-bold transition-colors"
                            :class="
                                sslForm.SSLCOMMERZ_ENABLED
                                    ? 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700/60 dark:bg-emerald-500/10 dark:text-emerald-300'
                                    : 'border-red-300 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-700/60 dark:bg-red-500/10 dark:text-red-300'
                            "
                            @click="!($page.props.is_demo_mode as boolean) && (sslForm.SSLCOMMERZ_ENABLED = !sslForm.SSLCOMMERZ_ENABLED)"
                        >
                            {{ __('Status') }}:
                            {{ sslForm.SSLCOMMERZ_ENABLED ? __('Active') : __('Disabled') }}
                        </button>
                    </div>
                    <p v-if="sslForm.errors.SSLCOMMERZ_ENABLED" class="text-sm text-red-500">{{ sslForm.errors.SSLCOMMERZ_ENABLED }}</p>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ __('Store ID') }}</label>
                            <input
                                :value="($page.props.is_demo_mode as boolean) ? '******' : sslForm.SSLCOMMERZ_STORE_ID"
                                @input="sslForm.SSLCOMMERZ_STORE_ID = ($event.target as HTMLInputElement).value"
                                :disabled="$page.props.is_demo_mode as boolean"
                                type="text"
                                name="sslcommerz_store_id"
                                autocomplete="off"
                                autocapitalize="off"
                                spellcheck="false"
                                class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                            <p v-if="sslForm.errors.SSLCOMMERZ_STORE_ID" class="mt-1 text-xs text-red-500">{{ sslForm.errors.SSLCOMMERZ_STORE_ID }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ __('Store Password') }}</label>
                            <div class="relative">
                                <input
                                    :value="($page.props.is_demo_mode as boolean) ? '******' : sslForm.SSLCOMMERZ_STORE_PASSWORD"
                                    @input="sslForm.SSLCOMMERZ_STORE_PASSWORD = ($event.target as HTMLInputElement).value"
                                    :disabled="$page.props.is_demo_mode as boolean"
                                    :type="showSslStorePassword ? 'text' : 'password'"
                                    name="sslcommerz_store_password"
                                    autocomplete="new-password"
                                    autocapitalize="off"
                                    spellcheck="false"
                                    class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 pe-10 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 end-0 inline-flex w-10 items-center justify-center text-slate-500 transition-colors hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                                    @click="showSslStorePassword = !showSslStorePassword"
                                >
                                    <Eye v-if="!showSslStorePassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="sslForm.errors.SSLCOMMERZ_STORE_PASSWORD" class="mt-1 text-xs text-red-500">{{ sslForm.errors.SSLCOMMERZ_STORE_PASSWORD }}</p>
                        </div>
                    </div>

                    <label class="mt-4 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800/50">
                        <input
                            v-model="sslForm.SSLCOMMERZ_SANDBOX"
                            :disabled="$page.props.is_demo_mode as boolean"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 disabled:opacity-60"
                        />
                        <span class="font-medium text-slate-700 dark:text-slate-200">{{ __('Use Sandbox Mode') }}</span>
                    </label>
                    <p v-if="sslForm.errors.SSLCOMMERZ_SANDBOX" class="mt-1 text-xs text-red-500">{{ sslForm.errors.SSLCOMMERZ_SANDBOX }}</p>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="sslForm.processing || ($page.props.is_demo_mode as boolean)"
                            class="inline-flex h-11 items-center gap-2 rounded-xl bg-primary-600 px-5 text-sm font-bold text-white transition-colors hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <ShieldCheck class="h-4 w-4" />
                            {{ sslForm.processing ? __('Saving...') : ($page.props.is_demo_mode as boolean) ? __('Save Disabled') : __('Save SSLCommerz') }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700/60 dark:bg-slate-900">
                <form class="space-y-6" autocomplete="off" @submit.prevent="saveStripe">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <h2 class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white">
                            <WalletCards class="h-4 w-4 text-indigo-600" /> Stripe
                        </h2>
                        <button
                            type="button"
                            class="inline-flex h-9 items-center rounded-lg border px-3 text-xs font-bold transition-colors"
                            :class="
                                stripeForm.STRIPE_ENABLED
                                    ? 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700/60 dark:bg-emerald-500/10 dark:text-emerald-300'
                                    : 'border-red-300 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-700/60 dark:bg-red-500/10 dark:text-red-300'
                            "
                            @click="!($page.props.is_demo_mode as boolean) && (stripeForm.STRIPE_ENABLED = !stripeForm.STRIPE_ENABLED)"
                        >
                            {{ __('Status') }}:
                            {{ stripeForm.STRIPE_ENABLED ? __('Active') : __('Disabled') }}
                        </button>
                    </div>
                    <p v-if="stripeForm.errors.STRIPE_ENABLED" class="text-sm text-red-500">{{ stripeForm.errors.STRIPE_ENABLED }}</p>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ __('Public Key') }}</label>
                            <input
                                :value="($page.props.is_demo_mode as boolean) ? '******' : stripeForm.STRIPE_PUBLIC_KEY"
                                @input="stripeForm.STRIPE_PUBLIC_KEY = ($event.target as HTMLInputElement).value"
                                :disabled="$page.props.is_demo_mode as boolean"
                                type="text"
                                name="stripe_public_key"
                                autocomplete="off"
                                autocapitalize="off"
                                spellcheck="false"
                                class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                            <p v-if="stripeForm.errors.STRIPE_PUBLIC_KEY" class="mt-1 text-xs text-red-500">{{ stripeForm.errors.STRIPE_PUBLIC_KEY }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ __('Secret Key') }}</label>
                            <div class="relative">
                                <input
                                    :value="($page.props.is_demo_mode as boolean) ? '******' : stripeForm.STRIPE_SECRET_KEY"
                                    @input="stripeForm.STRIPE_SECRET_KEY = ($event.target as HTMLInputElement).value"
                                    :disabled="$page.props.is_demo_mode as boolean"
                                    :type="showStripeSecretKey ? 'text' : 'password'"
                                    name="stripe_secret_key"
                                    autocomplete="new-password"
                                    autocapitalize="off"
                                    spellcheck="false"
                                    class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 pe-10 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 end-0 inline-flex w-10 items-center justify-center text-slate-500 transition-colors hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                                    @click="showStripeSecretKey = !showStripeSecretKey"
                                >
                                    <Eye v-if="!showStripeSecretKey" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="stripeForm.errors.STRIPE_SECRET_KEY" class="mt-1 text-xs text-red-500">{{ stripeForm.errors.STRIPE_SECRET_KEY }}</p>
                        </div>

                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="stripeForm.processing || ($page.props.is_demo_mode as boolean)"
                            class="inline-flex h-11 items-center gap-2 rounded-xl bg-primary-600 px-5 text-sm font-bold text-white transition-colors hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <ShieldCheck class="h-4 w-4" />
                            {{ stripeForm.processing ? __('Saving...') : ($page.props.is_demo_mode as boolean) ? __('Save Disabled') : __('Save Stripe') }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>
