<script setup lang="ts">
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import customer from '@/routes/customer';
import { CheckCircle2, Clock3, ArrowRight } from 'lucide-vue-next';

defineProps<{
    subscription: {
        id: number;
        status: string;
        plan?: { name?: string; duration_days?: number };
    } | null;
}>();
</script>

<template>
    <Head :title="__('Registration Submitted')" />

    <CustomerLayout>
        <div class="mx-auto max-w-3xl">
            <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-start gap-4">
                    <div class="rounded-xl bg-emerald-100 p-3 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <CheckCircle2 class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('Welcome to GymMaster') }}</h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{
                                subscription?.status === 'active'
                                    ? __('Your account was created successfully and your membership is now active.')
                                    : __('Your account was created successfully and your membership request is now pending admin approval.')
                            }}
                        </p>
                    </div>
                </div>

                <div
                    class="mt-6 rounded-xl border p-4"
                    :class="[
                        subscription?.status === 'active'
                            ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800/50 dark:bg-emerald-900/20'
                            : 'border-amber-200 bg-amber-50 dark:border-amber-800/50 dark:bg-amber-900/20',
                    ]"
                >
                    <div class="flex items-start gap-3">
                        <Component
                            :is="subscription?.status === 'active' ? CheckCircle2 : Clock3"
                            class="mt-0.5 h-5 w-5"
                            :class="subscription?.status === 'active' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'"
                        />
                        <div
                            class="text-sm"
                            :class="subscription?.status === 'active' ? 'text-emerald-800 dark:text-emerald-300' : 'text-amber-800 dark:text-amber-300'"
                        >
                            <p class="font-semibold">
                                {{ __('Current Status') }}: {{ subscription?.status === 'active' ? __('Active') : __('Pending') }}
                            </p>
                            <p class="mt-1">
                                {{ __('Plan') }}: <span class="font-medium">{{ subscription?.plan?.name || __('Selected Plan') }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    <Link
                        :href="customer.dashboard.url()"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-700"
                    >
                        {{ __('Go to Dashboard') }}
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
