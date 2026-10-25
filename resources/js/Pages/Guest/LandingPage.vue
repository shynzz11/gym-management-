<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import Toast from '@/Components/Common/Toast.vue';
import PlanFeatureList from '@/Components/Common/PlanFeatureList.vue';
import { ArrowRight, Dumbbell, Menu, ShieldCheck, Sparkles, Timer, X, Zap } from 'lucide-vue-next';
import { login, register, contact } from '@/routes';
import { privacy, terms, about } from '@/routes/legal';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import useCurrency from '@/Composables/useCurrency';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
    plans: Array<{
        id: number;
        name: string;
        price: string;
        duration_days: number;
        description: string;
        facilities?: string[];
        is_offer?: boolean;
        offer_price?: string;
        offer_ends_at?: string;
        is_default?: boolean;
    }>;
}>();

const mobileMenuOpen = ref(false);
const page = usePage();
const { formatCurrency } = useCurrency();
const settings = computed(() => (page.props.branding as any)?.business_settings || {});

// Flash Sale Countdown Logic
const currentTime = ref(new Date());
let timerInterval: any = null;

onMounted(() => {
    timerInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

const offerPlans = computed(() => {
    return props.plans.filter((plan) => {
        if (!plan.is_offer || !plan.offer_ends_at) return false;
        return new Date(plan.offer_ends_at) > currentTime.value;
    });
});

const getRemainingTime = (endsAt: string) => {
    const total = Date.parse(endsAt) - currentTime.value.getTime();
    const seconds = Math.floor((total / 1000) % 60);
    const minutes = Math.floor((total / 1000 / 60) % 60);
    const hours = Math.floor((total / (1000 * 60 * 60)) % 24);
    const days = Math.floor(total / (1000 * 60 * 60 * 24));

    return {
        total,
        days,
        hours,
        minutes,
        seconds,
        formatted: `${days}d ${hours}h ${minutes}m ${seconds}s`,
    };
};

const brandName = computed(() => settings.value.business_name || 'GymOS');
const brandTagline = computed(() => settings.value.tagline || 'Next-Gen Gym Management');
const logoUrl = computed(() => {
    const logo = settings.value.logo_url;
    const defaultLogo = 'https://api.dicebear.com/7.x/initials/svg?seed=Nexus&backgroundColor=0284c7';
    const src = logo || defaultLogo;
    return src.startsWith('http') ? src : '/storage/' + src;
});

const isOfferActive = (plan: any): boolean => {
    if (!plan.is_offer || !plan.offer_ends_at) {
        return false;
    }

    return new Date(plan.offer_ends_at) > currentTime.value;
};
</script>

<template>
    <Head :title="__('Transform Your Body')" />

    <div class="min-h-screen bg-white font-sans text-gray-900 transition-colors duration-300 dark:bg-gray-950 dark:text-white">
        <Toast />

        <!-- Navigation -->
        <nav class="sticky top-0 z-50 border-b border-gray-200 bg-white/90 backdrop-blur-md dark:border-gray-800 dark:bg-gray-950/90">
            <div class="container mx-auto flex items-center justify-between px-6 py-4">
                <!-- Logo -->
                <Link href="/" class="flex items-center gap-2">
                    <img :src="logoUrl" class="h-9 w-auto flex-shrink-0 object-contain" :alt="brandName" />
                    <div class="flex flex-col text-start leading-tight">
                        <span class="text-lg font-black tracking-tighter text-gray-900 dark:text-white">{{ brandName }}</span>
                        <span v-if="brandTagline" class="hidden text-[10px] text-gray-400 sm:block dark:text-gray-500">{{ brandTagline }}</span>
                    </div>
                </Link>

                <!-- Desktop Links -->
                <div class="hidden items-center gap-6 text-sm font-semibold text-gray-600 md:flex dark:text-gray-400">
                    <Link :href="about.url()" class="transition-colors hover:text-primary-600 dark:hover:text-primary-400">{{ __('About Us') }}</Link>
                    <Link :href="privacy.url()" class="transition-colors hover:text-primary-600 dark:hover:text-primary-400">{{
                        __('Privacy')
                    }}</Link>
                    <Link :href="terms.url()" class="transition-colors hover:text-primary-600 dark:hover:text-primary-400">{{ __('Terms') }}</Link>
                    <Link :href="contact.url()" class="transition-colors hover:text-primary-600 dark:hover:text-primary-400">{{
                        __('Contact')
                    }}</Link>
                </div>

                <!-- Desktop Auth + Theme -->
                <div class="hidden items-center gap-3 md:flex">
                    <ThemeToggle />
                    <Link
                        :href="login.url()"
                        class="px-3 py-2 text-sm font-bold text-gray-700 transition-colors hover:text-primary-600 dark:text-gray-200 dark:hover:text-primary-400"
                        >{{ __('Sign In') }}</Link
                    >
                    <Link
                        :href="register.url()"
                        class="rounded-full bg-primary-600 px-5 py-2 text-sm font-bold text-white shadow-lg shadow-primary-500/25 transition-all hover:scale-105 hover:bg-primary-700 active:scale-95"
                        >{{ __('Join Now') }}</Link
                    >
                </div>

                <!-- Mobile Hamburger -->
                <div class="flex items-center gap-2 md:hidden">
                    <ThemeToggle />
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="rounded-lg p-1.5 text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                    >
                        <Menu v-if="!mobileMenuOpen" class="h-5 w-5" />
                        <X v-else class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div
                v-show="mobileMenuOpen"
                class="space-y-3 border-t border-gray-200 bg-white px-6 py-4 md:hidden dark:border-gray-800 dark:bg-gray-950"
            >
                <Link
                    :href="about.url()"
                    class="block py-1 text-sm font-semibold text-gray-600 transition-colors hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
                    >{{ __('About Us') }}</Link
                >
                <Link
                    :href="privacy.url()"
                    class="block py-1 text-sm font-semibold text-gray-600 transition-colors hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
                    >{{ __('Privacy Policy') }}</Link
                >
                <Link
                    :href="terms.url()"
                    class="block py-1 text-sm font-semibold text-gray-600 transition-colors hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
                    >{{ __('Terms & Conditions') }}</Link
                >
                <Link
                    :href="contact.url()"
                    class="block py-1 text-sm font-semibold text-gray-600 transition-colors hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
                    >{{ __('Contact Us') }}</Link
                >
                <div class="flex flex-col gap-2 border-t border-gray-100 pt-3 dark:border-gray-800">
                    <Link
                        :href="login.url()"
                        class="py-2 text-center text-sm font-bold text-gray-700 transition-colors hover:text-primary-600 dark:text-gray-200"
                        >{{ __('Sign In') }}</Link
                    >
                    <Link
                        :href="register.url()"
                        class="rounded-xl bg-primary-600 px-5 py-3 text-center text-sm font-bold text-white transition-all hover:bg-primary-700"
                        >{{ __('Join Now') }}</Link
                    >
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <main class="relative z-10 container mx-auto flex flex-col items-center px-6 pt-20 pb-32 text-center">
            <div
                class="pointer-events-none absolute top-0 left-1/2 -z-10 h-[500px] w-[800px] -translate-x-1/2 rounded-full bg-primary-500/10 blur-[120px] dark:bg-primary-500/20"
            ></div>

            <div
                class="mb-8 inline-flex items-center gap-2 rounded-full border border-primary-200 bg-primary-50 px-4 py-1.5 text-xs font-semibold tracking-widest text-primary-700 uppercase shadow-sm dark:border-gray-800 dark:bg-gray-900/80 dark:text-gray-300"
            >
                <Zap class="h-4 w-4 text-primary-500" />
                <span>{{ brandTagline }}</span>
            </div>

            <h1 class="mb-8 max-w-4xl text-5xl leading-[1.1] font-black tracking-tight text-gray-900 md:text-7xl dark:text-white">
                {{ __('Your Fitness, Our Data.') }}
            </h1>

            <p class="mb-12 max-w-2xl text-lg leading-relaxed text-gray-500 md:text-xl dark:text-gray-400">
                {{ __('Powerful memberships, real-time progress, and smart gym operations in one high-energy platform.') }}
            </p>

            <div class="flex flex-col gap-4 sm:flex-row">
                <a
                    href="#plans"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-8 py-4 text-lg font-bold text-white transition-all hover:scale-105 hover:bg-primary-700 hover:shadow-[0_0_30px_-5px_var(--brand-primary)]"
                >
                    View Memberships <ArrowRight class="h-5 w-5" />
                </a>
                <Link
                    :href="contact.url()"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-gray-100 px-8 py-4 text-lg font-bold text-gray-800 transition-all hover:bg-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    {{ __('Contact Us') }}
                </Link>
            </div>

            <!-- Features -->
            <div class="mt-32 grid w-full max-w-6xl gap-8 text-left sm:grid-cols-3">
                <div
                    class="rounded-[2rem] border border-gray-200 bg-gray-50 p-8 shadow-sm transition-colors hover:border-primary-300 dark:border-gray-800/80 dark:bg-gray-900 dark:shadow-xl dark:shadow-black/50 dark:hover:border-primary-500/30"
                >
                    <div
                        class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl border border-primary-100 bg-primary-50 shadow-inner dark:border-gray-800 dark:bg-gray-950"
                    >
                        <ShieldCheck class="h-7 w-7 text-primary-500" />
                    </div>
                    <h3 class="mb-3 text-xl font-bold text-gray-900 dark:text-white">QR Check-in</h3>
                    <p class="text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                        Instantly access the facility using your digital membership card generated right on your mobile dashboard.
                    </p>
                </div>
                <div
                    class="rounded-[2rem] border border-gray-200 bg-gray-50 p-8 shadow-sm transition-colors hover:border-primary-300 dark:border-gray-800/80 dark:bg-gray-900 dark:shadow-xl dark:shadow-black/50 dark:hover:border-primary-500/30"
                >
                    <div
                        class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl border border-primary-100 bg-primary-50 shadow-inner dark:border-gray-800 dark:bg-gray-950"
                    >
                        <Zap class="h-7 w-7 text-primary-500" />
                    </div>
                    <h3 class="mb-3 text-xl font-bold text-gray-900 dark:text-white">Real-time Progress</h3>
                    <p class="text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                        Visualize your subscription longevity and never miss a beat with active renewal countdowns seamlessly tracked.
                    </p>
                </div>
                <div
                    class="rounded-[2rem] border border-gray-200 bg-gray-50 p-8 shadow-sm transition-colors hover:border-primary-300 dark:border-gray-800/80 dark:bg-gray-900 dark:shadow-xl dark:shadow-black/50 dark:hover:border-primary-500/30"
                >
                    <div
                        class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl border border-primary-100 bg-primary-50 shadow-inner dark:border-gray-800 dark:bg-gray-950"
                    >
                        <Dumbbell class="h-7 w-7 text-primary-500" />
                    </div>
                    <h3 class="mb-3 text-xl font-bold text-gray-900 dark:text-white">Tiered Flexibility</h3>
                    <p class="text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                        Choose the exact plan that fits your goals. Upgrade, downgrade, or pause easily from your unified portal.
                    </p>
                </div>
            </div>

            <!-- Flash Sale Section -->
            <section v-if="offerPlans.length > 0" class="animate-in fade-in zoom-in mt-20 w-full duration-700">
                <div
                    class="relative overflow-hidden rounded-[3rem] bg-orange-600 p-8 text-white shadow-2xl shadow-orange-500/20 md:p-16 dark:bg-orange-700"
                >
                    <!-- Background accent -->
                    <div class="absolute top-0 right-0 -mt-20 -mr-20 h-64 w-64 rounded-full bg-orange-400/20 blur-3xl"></div>

                    <div class="relative z-10 flex flex-col items-center gap-12 lg:flex-row">
                        <div class="text-center lg:text-left">
                            <div
                                class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/20 px-4 py-1.5 text-xs font-black tracking-widest uppercase"
                            >
                                <Sparkles class="h-4 w-4" />
                                {{ __('Limited Time Offer') }}
                            </div>
                            <h2 class="mb-6 text-4xl leading-tight font-black md:text-6xl">{{ __('Flash Sale') }}</h2>
                            <p class="mb-8 max-w-md text-lg font-medium text-orange-50">
                                {{ __('Grab these high-performance tiers at half the price. Once the timer hits zero, these deals vanish forever.') }}
                            </p>
                        </div>

                        <div class="grid w-full flex-1 gap-6 sm:grid-cols-2">
                            <div
                                v-for="plan in offerPlans"
                                :key="plan.id"
                                class="group relative overflow-hidden rounded-[2rem] bg-white p-6 text-slate-900 shadow-xl transition-all hover:scale-[1.02]"
                            >
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-xl font-bold">{{ plan.name }}</h3>
                                    <div
                                        class="flex items-center gap-2 rounded-full bg-orange-100 px-2.5 py-1 text-[10px] font-black tracking-tighter text-orange-600 uppercase"
                                    >
                                        <Timer :size="12" />
                                        {{ getRemainingTime(plan.offer_ends_at!).formatted }}
                                    </div>
                                </div>

                                <div class="mb-6 flex items-baseline gap-2">
                                    <span class="text-3xl font-black text-orange-600">{{ formatCurrency(plan.offer_price) }}</span>
                                    <span class="text-sm font-bold text-slate-400 line-through">{{ formatCurrency(plan.price) }}</span>
                                    <span class="text-xs font-medium text-slate-400">/ {{ plan.duration_days }} days</span>
                                </div>

                                <Link
                                    :href="register.url({ query: { plan_id: plan.id } })"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-orange-600 py-3 text-sm font-bold text-white transition-colors hover:bg-orange-700"
                                >
                                    {{ __('Claim Offer') }}
                                    <ArrowRight :size="16" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Live Plan Cards -->
            <div id="plans" class="mt-40 w-full border-t border-gray-200 pt-16 dark:border-gray-800/50">
                <h2 class="mb-4 text-3xl font-black text-gray-900 md:text-5xl dark:text-white">Choose Your Path</h2>
                <p class="mx-auto mb-16 max-w-xl text-gray-500 dark:text-gray-400">
                    Select a membership tier below to instantly provision your account and get your digital access pass.
                </p>

                <div class="mx-auto flex max-w-6xl flex-wrap justify-center gap-6">
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        class="group relative flex w-full max-w-[360px] flex-col overflow-hidden rounded-3xl border bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:bg-slate-900 min-h-[440px]"
                        :class="plan.is_default 
                            ? 'border-primary-500 ring-2 ring-primary-500/20 shadow-primary-500/10 scale-[1.02] z-10' 
                            : 'border-slate-200 hover:border-primary-300 dark:border-slate-800 dark:hover:border-primary-500/50'"
                    >
                        <!-- Default Badge -->
                        <div v-if="plan.is_default" class="absolute top-0 right-0 z-20">
                            <div class="bg-primary-500 px-4 py-1 text-[10px] font-black tracking-widest text-white uppercase rounded-bl-xl shadow-sm">
                                {{ __('Most Popular') }}
                            </div>
                        </div>

                        <div
                            class="absolute inset-0 bg-gradient-to-br from-primary-600/5 via-transparent to-transparent opacity-0 transition-opacity group-hover:opacity-100"
                            :class="plan.is_default ? 'opacity-40' : ''"
                        ></div>

                        <div class="relative z-10 flex h-full flex-col items-center text-center">
                            <div class="w-full">
                                <h3 class="text-xl leading-none font-bold text-slate-900 dark:text-white">{{ plan.name }}</h3>
                                <div class="mt-2 flex justify-center">
                                    <span
                                        class="inline-flex shrink-0 items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-[10px] font-semibold tracking-wide text-slate-600 uppercase dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        {{ plan.duration_days }} {{ __('days') }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-4 mb-3 flex flex-col items-center">
                                <div class="flex items-baseline justify-center gap-1" :class="{ 'opacity-50 line-through scale-75 -mb-1': isOfferActive(plan) }">
                                    <span class="text-4xl font-black tracking-tighter text-primary-600 dark:text-primary-400">{{
                                        formatCurrency(plan.price)
                                    }}</span>
                                </div>
                                <div v-if="isOfferActive(plan)" class="flex items-baseline justify-center gap-1 animate-in zoom-in-75 duration-300">
                                    <span class="text-4xl font-black tracking-tighter text-orange-600 dark:text-orange-500">{{
                                        formatCurrency(plan.offer_price)
                                    }}</span>
                                </div>
                            </div>

                            <p class="mb-4 text-xs leading-tight text-slate-600 dark:text-slate-300 max-w-[240px]">
                                {{ plan.description || __('No description available for this plan yet.') }}
                            </p>

                            <div class="w-full">
                                <div class="w-full rounded-2xl border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-800/50">
                                    <p class="mb-2 text-[10px] font-bold tracking-[0.12em] text-slate-500 uppercase dark:text-slate-400 text-center">
                                        {{ __('Included Facilities') }}
                                    </p>
                                    <div class="h-[90px] overflow-y-auto scrollbar-thin scrollbar-thumb-slate-200 dark:scrollbar-thumb-slate-700 pr-1 flex flex-col items-center">
                                        <PlanFeatureList :features="plan.facilities ?? []" :show-only-included="true" />
                                        <p v-if="(plan.facilities ?? []).length === 0" class="text-xs text-slate-500 dark:text-slate-400 text-center">
                                            {{ __('No facilities assigned to this plan yet.') }}
                                        </p>
                                    </div>
                                </div>

                                <Link
                                    :href="register.url({ query: { plan_id: plan.id } })"
                                    class="mt-4 block w-full rounded-xl bg-primary-600 py-3 text-center text-sm font-bold text-white transition-colors hover:bg-primary-700"
                                >
                                    {{ __('Select Plan') }}
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="plans.length === 0"
                        class="col-span-full rounded-[3rem] border border-dashed border-gray-300 bg-gray-50 py-20 text-center text-gray-500 dark:border-gray-800 dark:bg-gray-900/30"
                    >
                        <Dumbbell class="mx-auto mb-4 h-12 w-12 text-gray-300 dark:text-gray-700" />
                        <h4 class="mb-2 text-xl font-bold text-gray-700 dark:text-gray-300">No active plans</h4>
                        <p class="text-sm text-gray-500">Please check back later or contact administration.</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-gray-50 py-10 dark:border-gray-800 dark:bg-gray-950">
            <div class="container mx-auto px-6">
                <div class="flex flex-col items-center justify-between gap-6 md:flex-row">
                    <!-- Brand -->
                    <div class="flex items-center gap-2">
                        <img :src="logoUrl" class="h-7 w-auto flex-shrink-0 object-contain" :alt="brandName" />
                        <span class="font-black tracking-tight text-gray-900 dark:text-white">{{ brandName }}</span>
                    </div>

                    <!-- Legal Links -->
                    <div class="flex flex-wrap justify-center gap-6 text-sm font-medium text-gray-500 dark:text-gray-400">
                        <Link :href="about.url()" class="transition-colors hover:text-primary-600 dark:hover:text-primary-400">{{
                            __('About Us')
                        }}</Link>
                        <Link :href="privacy.url()" class="transition-colors hover:text-primary-600 dark:hover:text-primary-400">{{
                            __('Privacy Policy')
                        }}</Link>
                        <Link :href="terms.url()" class="transition-colors hover:text-primary-600 dark:hover:text-primary-400">{{
                            __('Terms & Conditions')
                        }}</Link>
                        <Link :href="contact.url()" class="transition-colors hover:text-primary-600 dark:hover:text-primary-400">{{
                            __('Contact Us')
                        }}</Link>
                    </div>

                    <!-- Copyright -->
                    <p class="text-sm text-gray-400 dark:text-gray-600">
                        &copy; {{ new Date().getFullYear() }} {{ brandName }}. {{ __('All rights reserved.') }}
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
