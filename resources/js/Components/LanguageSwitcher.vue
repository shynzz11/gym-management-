<script setup lang="ts">
import { switchMethod } from '@/routes/locale';
import { usePage, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const page = usePage();
const currentLocale = computed(() => page.props.locale as string);
const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const locales: Record<string, { name: string; flag: string }> = {
    en: { name: 'English', flag: '🇺🇸' },
    ar: { name: 'العربية', flag: '🇸🇦' },
};

const switchLocale = (locale: string) => {
    isOpen.value = false;
    // @ts-ignore
    window.location.href = switchMethod({ locale }).url;
};

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = (e: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
    document.removeEventListener('click', closeDropdown);
});
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <button
            @click.stop="toggleDropdown"
            class="flex items-center space-x-2 rounded-lg px-3 py-1.5 font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-primary-600 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-primary-400"
        >
            <span class="text-base">{{ locales[currentLocale]?.flag }}</span>
            <span class="hidden text-xs md:inline">{{ locales[currentLocale]?.name }}</span>
            <svg
                class="h-4 w-4 transition-transform duration-200"
                :class="{ 'rotate-180': isOpen }"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div
            v-show="isOpen"
            class="ring-opacity-5 absolute right-0 z-50 mt-2 w-48 origin-top-right transform rounded-xl bg-white py-1 shadow-lg ring-1 ring-black transition-all duration-200 dark:bg-gray-800"
            :class="isOpen ? 'scale-100 opacity-100' : 'scale-95 opacity-0'"
        >
            <button
                v-for="(data, locale) in locales"
                :key="locale"
                @click="switchLocale(locale as string)"
                class="group flex w-full items-center justify-between px-4 py-3 text-left text-sm text-gray-700 transition-colors first:rounded-t-xl last:rounded-b-xl hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                <div class="flex items-center space-x-3">
                    <span class="text-lg">{{ data.flag }}</span>
                    <span :class="{ 'font-bold text-primary-600 dark:text-primary-400': currentLocale === locale }">
                        {{ data.name }}
                    </span>
                </div>
                <svg
                    v-if="currentLocale === locale"
                    class="h-4 w-4 text-primary-600 dark:text-primary-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </button>
        </div>
    </div>
</template>
