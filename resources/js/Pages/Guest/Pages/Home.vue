<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { register } from '@/routes';

const openFaq = ref<number | null>(null);
const toggleFaq = (index: number | string) => {
    const idx = typeof index === 'string' ? parseInt(index) : index;
    openFaq.value = openFaq.value === idx ? null : idx;
};
</script>

<template>
    <PublicLayout>
        <div
            class="-mx-4 -my-12 min-h-screen bg-gray-50 font-sans text-gray-900 transition-colors duration-300 sm:-mx-6 lg:-mx-8 dark:bg-gray-900 dark:text-gray-100"
        >
            <!-- Hero Section -->
            <section class="relative overflow-hidden pt-20 pb-32">
                <div class="relative z-10 mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                    <div
                        class="animate-fade-in-up mb-8 inline-flex items-center rounded-full bg-primary-50 px-4 py-2 text-sm font-medium text-primary-700"
                    >
                        <span class="mr-2 flex h-2 w-2 rounded-full bg-primary-600"></span>
                        {{ __($page.props.branding.home?.hero?.badge) }}
                    </div>
                    <h1 class="mb-8 text-5xl leading-tight font-bold tracking-tight md:text-7xl">
                        {{ __($page.props.branding.home?.hero?.title_line_1) }} <br />
                        <span class="relative text-primary-600">
                            {{ __($page.props.branding.home?.hero?.title_line_2) }}
                            <svg class="absolute -bottom-1 left-0 -z-10 h-3 w-full text-primary-200" viewBox="0 0 100 10" preserveAspectRatio="none">
                                <path d="M0 5 Q 50 10 100 5 L 100 10 L 0 10 Z" fill="currentColor" />
                            </svg>
                        </span>
                    </h1>
                    <p class="mx-auto mb-10 max-w-2xl text-xl leading-relaxed text-gray-500">
                        {{ __($page.props.branding.home?.hero?.description) }}
                    </p>
                    <div class="mb-16 flex flex-col justify-center gap-4 sm:flex-row">
                        <Link
                            :href="register.url()"
                            class="transform rounded-xl bg-primary-600 px-8 py-4 text-lg font-bold text-white shadow-xl shadow-primary-500/30 transition-all hover:-translate-y-1 hover:bg-primary-700"
                        >
                            {{ __($page.props.branding.home?.hero?.cta_primary) }}
                        </Link>
                        <a
                            href="#video-showcase"
                            class="rounded-xl border border-gray-200 bg-white px-8 py-4 text-lg font-bold text-gray-700 transition-all hover:border-gray-300 hover:bg-gray-50"
                        >
                            {{ __($page.props.branding.home?.hero?.cta_secondary) }}
                        </a>
                    </div>

                    <!-- Dashboard Preview -->
                    <div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl">
                        <div class="absolute inset-x-0 top-0 flex h-8 items-center space-x-2 border-b border-gray-200 bg-gray-100 px-4">
                            <div class="h-3 w-3 rounded-full bg-red-400"></div>
                            <div class="h-3 w-3 rounded-full bg-yellow-400"></div>
                            <div class="h-3 w-3 rounded-full bg-green-400"></div>
                        </div>
                        <div class="bg-gray-50 px-8 pt-8 pb-12">
                            <!-- Mock Dashboard UI -->
                            <div class="grid grid-cols-12 gap-6 text-left">
                                <div class="col-span-3 h-64 rounded-xl bg-white shadow-sm"></div>
                                <div class="col-span-9 space-y-6">
                                    <div class="grid grid-cols-3 gap-6">
                                        <div class="h-32 rounded-xl bg-white shadow-sm"></div>
                                        <div class="h-32 rounded-xl bg-white shadow-sm"></div>
                                        <div class="h-32 rounded-xl bg-white shadow-sm"></div>
                                    </div>
                                    <div class="h-96 rounded-xl bg-white shadow-sm"></div>
                                </div>
                            </div>
                        </div>
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-gray-50/50 to-transparent"></div>
                    </div>
                </div>

                <!-- Background Decorations -->
                <div class="pointer-events-none absolute top-0 left-1/2 -z-10 h-full w-full -translate-x-1/2">
                    <div
                        class="animate-blob absolute top-20 left-10 h-72 w-72 rounded-full bg-primary-200 opacity-30 mix-blend-multiply blur-3xl filter"
                    ></div>
                    <div
                        class="animate-blob animation-delay-2000 absolute top-20 right-10 h-72 w-72 rounded-full bg-purple-200 opacity-30 mix-blend-multiply blur-3xl filter"
                    ></div>
                    <div
                        class="animate-blob animation-delay-4000 absolute -bottom-32 left-1/4 h-72 w-72 rounded-full bg-pink-200 opacity-30 mix-blend-multiply blur-3xl filter"
                    ></div>
                </div>
            </section>

            <!-- Video Showcase Section -->
            <section id="video-showcase" class="relative overflow-hidden bg-white py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-16 text-center">
                        <h2 class="mb-4 text-3xl font-bold text-gray-900">{{ __($page.props.branding.home?.video?.title) }}</h2>
                        <p class="mx-auto max-w-2xl text-gray-500">{{ __($page.props.branding.home?.video?.description) }}</p>
                    </div>
                    <div class="group relative aspect-video cursor-pointer overflow-hidden rounded-2xl bg-gray-900 shadow-2xl">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div
                                class="flex h-20 w-20 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm transition-transform group-hover:scale-110"
                            >
                                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-lg">
                                    <svg class="h-6 w-6 translate-x-0.5 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"
                                        />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <!-- Placeholder for actual video thumbnail -->
                        <div class="h-full w-full bg-gradient-to-br from-gray-800 to-gray-900 opacity-50"></div>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section id="features" class="bg-gray-50 py-24 transition-colors duration-300 dark:bg-gray-900">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-16 text-center">
                        <h2 class="mb-4 text-3xl font-bold text-gray-900 dark:text-white">{{ __($page.props.branding.home?.features?.title) }}</h2>
                        <p class="mx-auto max-w-2xl text-gray-500 dark:text-gray-400">{{ __($page.props.branding.home?.features?.description) }}</p>
                    </div>

                    <div class="grid gap-8 md:grid-cols-3">
                        <div
                            v-for="(feature, index) in $page.props.branding.home?.features?.items"
                            :key="index"
                            class="group rounded-2xl border border-gray-100 bg-white p-8 transition-all duration-300 hover:shadow-xl hover:shadow-gray-200/50 dark:border-gray-700 dark:bg-gray-800 dark:hover:shadow-black/30"
                        >
                            <div
                                class="mb-6 flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 text-2xl text-primary-600 shadow-sm transition-transform group-hover:scale-110 dark:bg-primary-900/20 dark:text-primary-400"
                            >
                                {{ feature.icon }}
                            </div>
                            <h3
                                class="mb-3 text-xl font-bold text-gray-900 transition-colors group-hover:text-primary-600 dark:text-white dark:group-hover:text-primary-400"
                            >
                                {{ __(feature.title) }}
                            </h3>
                            <p class="leading-relaxed text-gray-500 dark:text-gray-400">
                                {{ __(feature.description) }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Testimonials Section -->
            <section class="bg-white py-24 transition-colors duration-300 dark:bg-gray-800">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-16 text-center">
                        <h2 class="mb-4 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ __($page.props.branding.home?.testimonials?.title) }}
                        </h2>
                        <p class="mx-auto max-w-2xl text-gray-500 dark:text-gray-400">
                            {{ __($page.props.branding.home?.testimonials?.description) }}
                        </p>
                    </div>
                    <div class="grid gap-8 md:grid-cols-3">
                        <div
                            v-for="(testimonial, index) in $page.props.branding.home?.testimonials?.items"
                            :key="index"
                            class="rounded-2xl border border-gray-100 bg-gray-50 p-8 dark:border-gray-700 dark:bg-gray-700/50"
                        >
                            <p class="mb-6 text-gray-600 italic dark:text-gray-300">"{{ __(testimonial.content) }}"</p>
                            <div class="flex items-center">
                                <img :src="testimonial.avatar" class="mr-4 h-10 w-10 rounded-full" :alt="testimonial.name" />
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-white">{{ testimonial.name }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __(testimonial.role) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Pricing Section -->
            <section class="relative overflow-hidden bg-gray-900 py-24 text-white transition-colors duration-300 dark:bg-black">
                <!-- Background effects -->
                <div class="absolute top-0 left-0 h-full w-full overflow-hidden">
                    <div
                        class="absolute top-1/2 left-1/2 h-[800px] w-[800px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary-900/20 blur-[100px]"
                    ></div>
                </div>

                <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-16 text-center">
                        <h2 class="mb-4 text-3xl font-bold">{{ __($page.props.branding.home?.pricing?.title) }}</h2>
                        <p class="mx-auto max-w-2xl text-gray-400">{{ __($page.props.branding.home?.pricing?.description) }}</p>
                    </div>
                    <div class="grid items-center gap-8 md:grid-cols-3">
                        <div
                            v-for="(plan, index) in $page.props.branding.home?.pricing?.plans"
                            :key="index"
                            class="relative rounded-2xl border p-8 transition-all duration-300"
                            :class="
                                plan.highlight
                                    ? 'z-10 scale-105 border-primary-500 bg-primary-600 shadow-2xl'
                                    : 'border-gray-700 bg-gray-800 hover:border-gray-600 dark:bg-gray-900'
                            "
                        >
                            <h3 class="mb-2 text-xl font-bold">{{ __(plan.name) }}</h3>
                            <div class="mb-6 text-4xl font-bold">{{ plan.price }}<span class="text-lg font-normal opacity-70">/mo</span></div>
                            <ul class="mb-8 space-y-4">
                                <li v-for="feature in plan.features" :key="feature" class="flex items-center">
                                    <svg
                                        class="mr-3 h-5 w-5"
                                        :class="plan.highlight ? 'text-white' : 'text-primary-400'"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ __(feature) }}
                                </li>
                            </ul>
                            <button
                                class="w-full rounded-lg py-3 font-bold transition-colors"
                                :class="plan.highlight ? 'bg-white text-primary-600 hover:bg-gray-50' : 'bg-gray-700 text-white hover:bg-gray-600'"
                            >
                                {{ __(plan.cta) }}
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FAQ Section -->
            <section class="bg-white py-24 transition-colors duration-300 dark:bg-gray-800">
                <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-16 text-center">
                        <h2 class="mb-4 text-3xl font-bold text-gray-900 dark:text-white">{{ __($page.props.branding.home?.faq?.title) }}</h2>
                    </div>
                    <div class="space-y-4">
                        <div
                            v-for="(faq, index) in $page.props.branding.home?.faq?.items"
                            :key="index"
                            class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                        >
                            <button
                                @click="toggleFaq(index)"
                                class="flex w-full items-center justify-between bg-gray-50 px-6 py-4 text-left font-bold text-gray-900 transition-colors hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600"
                            >
                                {{ __(faq.question) }}
                                <svg
                                    class="h-5 w-5 transform transition-transform"
                                    :class="openFaq === index ? 'rotate-180' : ''"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div
                                v-show="openFaq === index"
                                class="border-t border-gray-200 bg-white px-6 py-4 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            >
                                {{ __(faq.answer) }}
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>

<style scoped>
.animate-blob {
    animation: blob 7s infinite;
}
.animation-delay-2000 {
    animation-delay: 2s;
}
.animation-delay-4000 {
    animation-delay: 4s;
}
@keyframes blob {
    0% {
        transform: translate(0px, 0px) scale(1);
    }
    33% {
        transform: translate(30px, -50px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
    100% {
        transform: translate(0px, 0px) scale(1);
    }
}
</style>
