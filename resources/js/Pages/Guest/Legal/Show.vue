<script setup lang="ts">
import GuestLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { FileText, Home, ChevronRight } from 'lucide-vue-next';

const props = defineProps<{
    title: string;
    content: string | null;
}>();

const renderedContent = computed(() => {
    if (!props.content) return '';

    // Content is already HTML from the rich text editor
    return props.content;
});

const lastUpdated = computed(() => {
    return new Date().toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});
</script>

<template>
    <Head :title="__(title)" />
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
            <span class="font-medium text-gray-900 dark:text-white">{{ __(title) }}</span>
        </nav>

        <div class="animate-fade-in-up overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-lg dark:border-gray-800 dark:bg-gray-900">
            <!-- Header Section -->
            <div
                class="border-b border-gray-200 bg-gradient-to-r from-primary-50 to-primary-100 px-8 py-10 sm:px-12 dark:border-gray-800 dark:from-gray-800 dark:to-gray-900"
            >
                <div class="flex items-start gap-4">
                    <div class="hidden h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-md sm:flex dark:bg-gray-900">
                        <FileText class="h-8 w-8 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div class="flex-1">
                        <h1 class="mb-2 text-3xl font-extrabold text-gray-900 sm:text-4xl dark:text-white">
                            {{ __(title) }}
                        </h1>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Last updated') }}: {{ lastUpdated }}</p>
                    </div>
                </div>
            </div>

            <!-- Content Section -->
            <div class="px-8 py-10 sm:px-12">
                <div
                    v-if="content"
                    class="prose prose-lg max-w-none dark:prose-invert prose-headings:scroll-mt-20 prose-headings:font-bold prose-headings:text-gray-900 dark:prose-headings:text-white prose-h1:mt-8 prose-h1:mb-6 prose-h1:border-b prose-h1:border-gray-200 prose-h1:pb-3 prose-h1:text-3xl dark:prose-h1:border-gray-700 prose-h2:mt-8 prose-h2:mb-4 prose-h2:text-2xl prose-h3:mt-6 prose-h3:mb-3 prose-h3:text-xl prose-p:mb-4 prose-p:leading-relaxed prose-p:text-gray-600 dark:prose-p:text-gray-300 prose-a:font-medium prose-a:text-primary-600 prose-a:no-underline hover:prose-a:underline dark:prose-a:text-primary-400 prose-blockquote:border-l-4 prose-blockquote:border-primary-500 prose-blockquote:pl-4 prose-blockquote:text-gray-700 prose-blockquote:italic dark:prose-blockquote:text-gray-300 prose-strong:font-semibold prose-strong:text-gray-900 dark:prose-strong:text-white prose-code:rounded prose-code:bg-gray-100 prose-code:px-1.5 prose-code:py-0.5 prose-code:font-mono prose-code:text-sm prose-code:text-primary-600 dark:prose-code:bg-gray-900 dark:prose-code:text-primary-400 prose-pre:rounded-lg prose-pre:bg-gray-900 prose-pre:p-4 prose-pre:text-gray-100 dark:prose-pre:bg-black prose-ol:my-6 prose-ol:list-decimal prose-ol:pl-6 prose-ul:my-6 prose-ul:list-disc prose-ul:pl-6 prose-li:my-2 prose-li:text-gray-600 dark:prose-li:text-gray-300 prose-table:w-full prose-table:border-collapse prose-th:bg-gray-50 prose-th:p-3 prose-th:text-left prose-th:font-semibold dark:prose-th:bg-gray-700 prose-td:border-t prose-td:border-gray-200 prose-td:p-3 dark:prose-td:border-gray-700 prose-hr:my-8 prose-hr:border-gray-200 dark:prose-hr:border-gray-700"
                    v-html="renderedContent"
                ></div>

                <!-- Empty State -->
                <div v-else class="py-16 text-center">
                    <div
                        class="mb-6 inline-flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-gray-100 to-gray-200 shadow-inner dark:from-gray-700 dark:to-gray-800"
                    >
                        <FileText class="h-12 w-12 text-gray-400 dark:text-gray-500" />
                    </div>
                    <h3 class="mb-2 text-xl font-bold text-gray-900 dark:text-white">
                        {{ __('Content Coming Soon') }}
                    </h3>
                    <p class="mx-auto mb-6 max-w-md text-gray-500 dark:text-gray-400">
                        {{ __('This page is currently being updated. Please check back later for more information.') }}
                    </p>
                    <Link
                        href="/"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-6 py-3 font-medium text-white shadow-lg shadow-primary-500/30 transition-all hover:bg-primary-700 hover:shadow-primary-500/50"
                    >
                        <Home class="h-4 w-4" />
                        {{ __('Return Home') }}
                    </Link>
                </div>
            </div>

            <!-- Footer Info -->
            <div v-if="content" class="border-t border-gray-200 bg-gray-50 px-8 py-6 sm:px-12 dark:border-gray-800 dark:bg-gray-900/50">
                <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('If you have any questions about this policy, please') }}
                        <a href="mailto:support@example.com" class="font-medium text-primary-600 hover:underline dark:text-primary-400">{{
                            __('contact us')
                        }}</a
                        >.
                    </p>
                    <Link
                        href="/"
                        class="inline-flex items-center gap-2 text-sm text-gray-600 transition-colors hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
                    >
                        <Home class="h-4 w-4" />
                        {{ __('Back to Home') }}
                    </Link>
                </div>
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

/* Smooth scroll behavior for anchor links */
:deep(a[href^='#']) {
    scroll-behavior: smooth;
}

/* Custom scrollbar for content */
:deep(.prose) {
    scrollbar-width: thin;
    scrollbar-color: rgb(209 213 219) transparent;
}

:deep(.prose)::-webkit-scrollbar {
    width: 8px;
}

:deep(.prose)::-webkit-scrollbar-track {
    background: transparent;
}

:deep(.prose)::-webkit-scrollbar-thumb {
    background-color: rgb(209 213 219);
    border-radius: 4px;
}

.dark :deep(.prose)::-webkit-scrollbar-thumb {
    background-color: rgb(75 85 99);
}
</style>
