<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Home, ChevronRight, Mail, Phone, MapPin, Facebook, Twitter, Instagram } from 'lucide-vue-next';
import { computed } from 'vue';
import GuestLayout from '@/Layouts/PublicLayout.vue';

// Access business settings from props
const page = usePage();
const businessSettings = computed(() => (page.props.branding as any)?.business_settings || {});

const brandName = computed(() => businessSettings.value.business_name || 'GymOS');

const contactEmail = computed(() => businessSettings.value.contact_email || 'contact@gymos.io');
const contactPhone = computed(() => businessSettings.value.contact_phone || '+1 (555) 000-1234');
const contactAddress = computed(() => businessSettings.value.contact_address || '123 Fitness Ave, San Francisco, CA 94103');

const socialFacebook = computed(() => businessSettings.value.social_facebook || '');
const socialTwitter = computed(() => businessSettings.value.social_twitter || '');
const socialInstagram = computed(() => businessSettings.value.social_instagram || '');

const hasSocialLinks = computed(() => socialFacebook.value || socialTwitter.value || socialInstagram.value);
</script>

<template>
    <Head :title="__('Contact Us')" />
    <GuestLayout>
        <!-- Breadcrumb -->
        <nav class="animate-fade-in mb-6 flex items-center space-x-2 text-sm">
            <Link
                href="/"
                class="flex items-center text-gray-500 transition-colors hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
            >
                <Home class="mr-1 h-4 w-4" />
                {{ __('Home') }}
            </Link>
            <ChevronRight class="h-4 w-4 text-gray-400" />
            <span class="font-medium text-gray-900 dark:text-white">{{ __('Contact Us') }}</span>
        </nav>

        <div class="animate-fade-in-up overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-lg dark:border-gray-800 dark:bg-gray-900">
            <!-- Header Section -->
            <div
                class="border-b border-gray-200 bg-gradient-to-r from-primary-50 to-primary-100 px-8 py-10 sm:px-12 dark:border-gray-800 dark:from-gray-800 dark:to-gray-900"
            >
                <div class="flex items-start gap-4">
                    <div class="hidden h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-md sm:flex dark:bg-gray-900">
                        <Mail class="h-8 w-8 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div class="flex-1">
                        <h1 class="mb-2 text-3xl font-extrabold text-gray-900 sm:text-4xl dark:text-white">
                            {{ __('Contact Us') }}
                        </h1>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ __('Get in touch with :name. We would love to hear from you.', { name: brandName }) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Content Section -->
            <div class="px-8 py-10 sm:px-12">
                <div class="grid grid-cols-1 gap-10 md:grid-cols-2">
                    <!-- Contact Information -->
                    <div class="space-y-8">
                        <div>
                            <h3 class="mb-6 border-b border-gray-100 pb-2 text-xl font-bold text-gray-900 dark:border-gray-700 dark:text-white">
                                {{ __('Get in Touch') }}
                            </h3>
                            <div class="space-y-6">
                                <div
                                    class="flex items-start gap-4 rounded-xl bg-gray-50 p-4 transition-colors hover:bg-gray-100 dark:bg-gray-700/50 dark:hover:bg-gray-700"
                                >
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white text-primary-500 shadow-sm dark:bg-gray-800"
                                    >
                                        <Mail :size="24" />
                                    </div>
                                    <div>
                                        <p class="mb-1 text-sm font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                            {{ __('Email Us') }}
                                        </p>
                                        <a
                                            :href="'mailto:' + contactEmail"
                                            class="text-lg font-bold break-all text-gray-900 transition-colors hover:text-primary-600 dark:text-white"
                                        >
                                            {{ contactEmail }}
                                        </a>
                                    </div>
                                </div>

                                <div
                                    class="flex items-start gap-4 rounded-xl bg-gray-50 p-4 transition-colors hover:bg-gray-100 dark:bg-gray-700/50 dark:hover:bg-gray-700"
                                >
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white text-primary-500 shadow-sm dark:bg-gray-800"
                                    >
                                        <Phone :size="24" />
                                    </div>
                                    <div>
                                        <p class="mb-1 text-sm font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                            {{ __('Call Us') }}
                                        </p>
                                        <a
                                            :href="'tel:' + contactPhone"
                                            class="text-lg font-bold text-gray-900 transition-colors hover:text-primary-600 dark:text-white"
                                        >
                                            {{ contactPhone }}
                                        </a>
                                    </div>
                                </div>

                                <div
                                    class="flex items-start gap-4 rounded-xl bg-gray-50 p-4 transition-colors hover:bg-gray-100 dark:bg-gray-700/50 dark:hover:bg-gray-700"
                                >
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white text-primary-500 shadow-sm dark:bg-gray-800"
                                    >
                                        <MapPin :size="24" />
                                    </div>
                                    <div>
                                        <p class="mb-1 text-sm font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                            {{ __('Visit Us') }}
                                        </p>
                                        <span class="text-lg font-bold text-gray-900 dark:text-white">{{ contactAddress }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Media -->
                    <div v-if="hasSocialLinks">
                        <h3 class="mb-6 border-b border-gray-100 pb-2 text-xl font-bold text-gray-900 dark:border-gray-700 dark:text-white">
                            {{ __('Social Presence') }}
                        </h3>
                        <div class="grid gap-4">
                            <a
                                v-if="businessSettings.social_facebook"
                                :href="businessSettings.social_facebook"
                                target="_blank"
                                class="group flex items-center gap-4 rounded-xl bg-blue-50 p-4 text-blue-700 transition-all hover:bg-blue-100 dark:bg-blue-900/10 dark:text-blue-400 dark:hover:bg-blue-900/20"
                            >
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 transition-transform group-hover:scale-110 dark:bg-blue-900/30"
                                >
                                    <Facebook :size="20" />
                                </div>
                                <span class="text-lg font-bold">Facebook</span>
                            </a>
                            <a
                                v-if="businessSettings.social_twitter"
                                :href="businessSettings.social_twitter"
                                target="_blank"
                                class="group flex items-center gap-4 rounded-xl bg-sky-50 p-4 text-sky-700 transition-all hover:bg-sky-100 dark:bg-sky-900/10 dark:text-sky-400 dark:hover:bg-sky-900/20"
                            >
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-sky-100 transition-transform group-hover:scale-110 dark:bg-sky-900/30"
                                >
                                    <Twitter :size="20" />
                                </div>
                                <span class="text-lg font-bold">Twitter</span>
                            </a>
                            <a
                                v-if="businessSettings.social_instagram"
                                :href="businessSettings.social_instagram"
                                target="_blank"
                                class="group flex items-center gap-4 rounded-xl bg-pink-50 p-4 text-pink-700 transition-all hover:bg-pink-100 dark:bg-pink-900/10 dark:text-pink-400 dark:hover:bg-pink-900/20"
                            >
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-pink-100 transition-transform group-hover:scale-110 dark:bg-pink-900/30"
                                >
                                    <Instagram :size="20" />
                                </div>
                                <span class="text-lg font-bold">Instagram</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom info -->
            <div class="border-t border-gray-200 bg-gray-50 px-8 py-6 sm:px-12 dark:border-gray-700 dark:bg-gray-900/50">
                <Link
                    href="/"
                    class="inline-flex items-center gap-2 text-sm text-gray-600 transition-colors hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
                >
                    <Home class="h-4 w-4" />
                    {{ __('Back to Home') }}
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

.animate-fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
