<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { login } from '@/routes/admin';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import Toast from '@/Components/Common/Toast.vue';
import { ref, computed } from 'vue';

defineProps<{
    status?: string;
    canResetPassword?: boolean;
}>();

const page = usePage();
const businessSettings = computed(() => (page.props.branding as any)?.business_settings || {});
const logoUrl = computed(() => {
    const logo = businessSettings.value.logo_url;
    const defaultLogo = 'https://api.dicebear.com/7.x/initials/svg?seed=Nexus&backgroundColor=0284c7';
    const src = logo || defaultLogo;
    return src.startsWith('http') ? src : '/storage/' + src;
});
const brandName = computed(() => businessSettings.value.business_name || 'GymOS');
const brandTagline = computed(() => businessSettings.value.tagline || 'Next-Gen Gym Management');
const appName = computed(() => brandName.value);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const isAuthenticating = ref(false);

const submit = () => {
    isAuthenticating.value = true;
    form.post(login.url(), {
        onFinish: () => {
            form.reset('password');
            isAuthenticating.value = false;
        },
        onError: () => {
            isAuthenticating.value = false;
        },
    });
};

const togglePassword = () => {
    showPassword.value = !showPassword.value;
};

const fillDemoCredentials = (email: string, pass: string) => {
    form.email = email;
    form.password = pass;
    form.remember = true;
};
</script>

<template>
    <Head :title="__('Admin Login')" />
    <Toast />

    <div
        class="flex min-h-screen flex-col bg-white font-sans text-slate-700 transition-colors duration-300 lg:flex-row dark:bg-gray-900 dark:text-slate-300"
    >
        <!-- Left Side: System Branding/Information -->
        <div class="branded-bg relative flex flex-col items-center justify-center overflow-hidden p-12 text-white lg:w-1/2">
            <!-- Decorative Shapes -->
            <div class="pointer-events-none absolute top-0 left-0 h-full w-full opacity-10">
                <div class="animate-float absolute -top-24 -left-24 h-96 w-96 rounded-full bg-white mix-blend-overlay blur-3xl filter"></div>
                <div
                    class="animate-float absolute right-0 bottom-1/4 h-64 w-64 rounded-full bg-white mix-blend-overlay blur-3xl filter"
                    style="animation-delay: 2s"
                ></div>
            </div>

            <div class="relative z-10 max-w-lg text-center lg:text-left">
                <Link
                    href="/"
                    class="mb-8 inline-flex h-20 w-20 items-center justify-center overflow-hidden rounded-3xl border border-white/20 bg-white/10 text-3xl text-white backdrop-blur-md"
                >
                    <img :src="logoUrl" class="h-full w-full object-contain p-2" :alt="brandName" />
                </Link>
                <h1 class="mb-6 text-4xl leading-tight font-bold lg:text-6xl">{{ brandName }}</h1>
                <p class="mb-12 text-lg leading-relaxed font-light text-primary-100 lg:text-xl">
                    {{ brandTagline }}
                </p>

                <!-- Feature Checklist -->
                <div class="space-y-4">
                    <div v-for="(feature, index) in $page.props.branding.admin_login?.features" :key="index" class="flex items-center space-x-3">
                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-400/20 text-primary-200">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 512 512">
                                <path
                                    d="M470.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L192 338.7 425.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"
                                />
                            </svg>
                        </div>
                        <span class="font-medium text-primary-50">{{ __(feature) }}</span>
                    </div>
                </div>
            </div>

            <!-- Footer Copyright (Left) -->
            <div class="absolute bottom-8 left-12 hidden text-sm text-primary-200/50 lg:block">
                &copy; {{ new Date().getFullYear() }} {{ appName }} Inc. All rights reserved.
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="relative flex items-center justify-center bg-white p-6 transition-colors duration-300 md:p-8 lg:w-1/2 dark:bg-gray-900">
            <div class="absolute top-4 right-4 flex items-center space-x-2">
                <ThemeToggle />
            </div>

            <div class="w-full max-w-md">
                <!-- Mobile Branding (Only visible on small screens) -->
                <div class="mb-10 text-center lg:hidden">
                    <Link href="/" class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-primary-600 text-white">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 320 512">
                            <path
                                d="M296 160H180.6l42.6-129.8C227.2 15 215.7 0 200 0H56C44 0 33.8 8.9 32.2 20.8l-32 240C-1.7 275.2 25.2 288 40 288h115.4l-42.6 129.8C108.8 434.9 120.3 448 136 448h144c12 0 22.2-8.9 23.8-20.8l32-240c1.9-14.3-9.2-27.2-24-27.2zM250.7 220.3l-32 240c-.6 4.3-4.1 7.7-8.7 7.7h-144c-4.1 0-7.7-2.9-8.4-7L100.2 332H40c-4.1 0-7.7-2.9-8.4-7L-11.4 69.8c-.8-5.8 3.7-10.9 9.5-10.9h144c4.1 0 7.7 2.9 8.4 7L167.8 196H228c4.1 0 7.7 2.9 8.4 7l24.3 15.3z"
                            />
                        </svg>
                    </Link>
                    <h2 class="text-2xl font-bold text-slate-800 dark:text-white">{{ __($page.props.branding.admin_login?.welcome_back) }}</h2>
                </div>

                <!-- Header -->
                <div class="mb-10 hidden lg:block">
                    <h2 class="mb-2 text-2xl font-bold text-slate-800 dark:text-white">{{ __($page.props.branding.admin_login?.form_title) }}</h2>
                    <p class="text-slate-500 dark:text-slate-400">{{ __($page.props.branding.admin_login?.form_subtitle) }}</p>
                </div>



                <div
                    v-if="status"
                    class="mb-6 rounded-2xl border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-600 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400"
                >
                    {{ status }}
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="mb-2 ml-1 block text-sm font-semibold text-slate-700 dark:text-slate-300" for="email">{{
                            __('Email Address')
                        }}</label>
                        <div class="group relative">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition-colors duration-300 group-focus-within:text-primary-600 dark:group-focus-within:text-primary-400"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                    ></path>
                                </svg>
                            </span>
                            <input
                                id="email"
                                type="email"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pr-4 pl-11 font-medium text-slate-700 transition-all duration-300 outline-none placeholder:text-slate-400 focus:border-primary-500 focus:bg-white focus:ring-4 focus:ring-primary-500/10 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:focus:bg-gray-800 dark:focus:ring-primary-500/20"
                                :placeholder="__('name@company.com')"
                                v-model="form.email"
                                required
                                autofocus
                            />
                        </div>
                        <div v-if="form.errors.email" class="mt-2 ml-1 text-sm text-red-600">{{ form.errors.email }}</div>
                    </div>

                    <div>
                        <div class="mb-2 flex justify-between px-1">
                            <label class="text-sm font-semibold text-slate-700 dark:text-slate-300" for="password">{{ __('Password') }}</label>
                            <a
                                v-if="canResetPassword"
                                href="#"
                                class="text-xs font-bold text-primary-600 transition-colors hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                                >{{ __('Forgot?') }}</a
                            >
                        </div>
                        <div class="group relative">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition-colors duration-300 group-focus-within:text-primary-600 dark:group-focus-within:text-primary-400"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 448 512">
                                    <path
                                        d="M144 144v48H304V144c0-44.2-35.8-80-80-80s-80 35.8-80 80zM80 192V144C80 64.5 144.5 0 224 0s144 64.5 144 144v48h16c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V256c0-35.3 28.7-64 64-64H80z"
                                    />
                                </svg>
                            </span>
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pr-12 pl-11 font-medium text-slate-700 transition-all duration-300 outline-none placeholder:text-slate-400 focus:border-primary-500 focus:bg-white focus:ring-4 focus:ring-primary-500/10 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:focus:bg-gray-800 dark:focus:ring-primary-500/20"
                                :placeholder="__('••••••••')"
                                v-model="form.password"
                                required
                            />
                            <button
                                type="button"
                                @click="togglePassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 transition-colors hover:text-slate-600 focus:outline-none dark:hover:text-slate-300"
                            >
                                <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    ></path>
                                </svg>
                                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                    ></path>
                                </svg>
                            </button>
                        </div>
                        <div v-if="form.errors.password" class="mt-2 ml-1 text-sm text-red-600">{{ form.errors.password }}</div>
                    </div>

                    <div class="flex items-center px-1">
                        <input
                            type="checkbox"
                            id="remember"
                            v-model="form.remember"
                            class="highlight-primary h-4 w-4 cursor-pointer rounded border-slate-300 bg-gray-100 text-primary-600 transition-all focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                        />
                        <label for="remember" class="ml-2 cursor-pointer text-sm font-medium text-slate-600 select-none dark:text-slate-400">{{
                            __('Stay signed in')
                        }}</label>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing || isAuthenticating"
                        :class="{ 'btn-loading': form.processing || isAuthenticating }"
                        class="flex w-full transform items-center justify-center space-x-2 rounded-2xl bg-primary-600 py-3 font-bold text-white shadow-xl shadow-primary-200 transition-all hover:bg-primary-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-75 dark:shadow-none"
                    >
                        <span v-if="form.processing || isAuthenticating">{{ __('Logging in...') }}</span>
                        <span v-else>{{ __('Login to Platform') }}</span>
                        <svg
                            v-if="!(form.processing || isAuthenticating)"
                            class="h-4 w-4 transition-transform group-hover:translate-x-1"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                        <svg
                            v-else
                            class="mr-3 -ml-1 h-5 w-5 animate-spin text-white"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                    </button>
                </form>

                <!-- Demo Mode Helper Widget -->
                <div v-if="$page.props.is_demo_mode" class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 transition-all hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-slate-600">
                    <div class="mb-3 flex items-center gap-2 font-bold tracking-widest text-slate-500 uppercase dark:text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="text-[10px]">Demo Account</span>
                    </div>
                    <div class="space-y-2">
                        <button type="button" @click="fillDemoCredentials('admin@admin.com', '12345678')" class="group relative flex w-full cursor-pointer flex-col items-start rounded-xl bg-slate-50 p-3 text-left transition-colors hover:bg-slate-100 dark:bg-slate-700/30 dark:hover:bg-slate-700/50">
                            <div class="flex w-full items-center justify-between">
                                <span class="text-base font-bold text-slate-800 dark:text-white">Super Admin</span>
                                <span class="rounded bg-primary-100 px-3 py-1 text-xs font-bold text-primary-700 opacity-0 transition-opacity group-hover:bg-primary-200 group-hover:opacity-100 dark:bg-primary-900/50 dark:text-primary-300 dark:group-hover:bg-primary-800/80">
                                    Auto-fill
                                </span>
                            </div>
                            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Email: admin@admin.com</span>
                            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Pass: 12345678</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

.branded-bg {
    background: linear-gradient(135deg, var(--color-primary-900) 0%, var(--color-primary-600) 100%);
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-20px);
    }
}

.input-focus {
    transition: all 0.3s ease;
}

.input-focus:focus {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.1);
}

.btn-loading {
    pointer-events: none;
    opacity: 0.8;
}
</style>
