<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { index, update } from '@/routes/admin/business/settings';
import { Settings, DollarSign, Globe, MapPin, Search, Clock } from 'lucide-vue-next';

const props = defineProps<{
    settings: {
        currency_symbol: string;
        timezone: string;
        country: string;
    };
}>();

const form = useForm({
    currency_symbol: props.settings.currency_symbol,
    timezone: props.settings.timezone,
    country: props.settings.country,
});

const submit = () => {
    form.post(update.url(), {
        preserveScroll: true,
        onSuccess: () => {
            // Form will be reset with new values from server
        },
    });
};

// Search states
const currencySearch = ref('');
const timezoneSearch = ref('');
const countrySearch = ref('');

// Common timezones list with regions
const timezones = [
    'UTC',
    'America/New_York',
    'America/Chicago',
    'America/Denver',
    'America/Los_Angeles',
    'America/Toronto',
    'America/Mexico_City',
    'America/Sao_Paulo',
    'America/Argentina/Buenos_Aires',
    'Europe/London',
    'Europe/Paris',
    'Europe/Berlin',
    'Europe/Rome',
    'Europe/Moscow',
    'Europe/Istanbul',
    'Africa/Cairo',
    'Africa/Johannesburg',
    'Asia/Dubai',
    'Asia/Karachi',
    'Asia/Kolkata',
    'Asia/Dhaka',
    'Asia/Bangkok',
    'Asia/Singapore',
    'Asia/Hong_Kong',
    'Asia/Tokyo',
    'Asia/Seoul',
    'Asia/Shanghai',
    'Australia/Sydney',
    'Australia/Melbourne',
    'Pacific/Auckland',
    'Pacific/Fiji',
];

// Get current time and UTC offset for a timezone
const getTimezoneInfo = (timezone: string) => {
    try {
        const now = new Date();

        // Get formatted time
        const timeFormatter = new Intl.DateTimeFormat('en-US', {
            timeZone: timezone,
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
        });
        const time = timeFormatter.format(now);

        // Calculate UTC offset
        const utcDate = new Date(now.toLocaleString('en-US', { timeZone: 'UTC' }));
        const tzDate = new Date(now.toLocaleString('en-US', { timeZone: timezone }));
        const offsetMinutes = (tzDate.getTime() - utcDate.getTime()) / (1000 * 60);
        const offsetHours = offsetMinutes / 60;

        // Format offset as UTC+X or UTC-X
        const offsetSign = offsetHours >= 0 ? '+' : '';
        const offset = `UTC${offsetSign}${offsetHours}`;

        return { time, offset };
    } catch (e) {
        return { time: '', offset: 'UTC' };
    }
};

// Filtered timezones based on search
const filteredTimezones = computed(() => {
    if (!timezoneSearch.value) return timezones;
    const search = timezoneSearch.value.toLowerCase();
    return timezones.filter((tz) => tz.toLowerCase().includes(search));
});

// Common countries list
const countries = [
    'Afghanistan',
    'Albania',
    'Algeria',
    'Argentina',
    'Australia',
    'Austria',
    'Bangladesh',
    'Belgium',
    'Brazil',
    'Canada',
    'China',
    'Colombia',
    'Denmark',
    'Egypt',
    'Finland',
    'France',
    'Germany',
    'Greece',
    'India',
    'Indonesia',
    'Iran',
    'Iraq',
    'Ireland',
    'Italy',
    'Japan',
    'Kenya',
    'Malaysia',
    'Mexico',
    'Netherlands',
    'New Zealand',
    'Nigeria',
    'Norway',
    'Pakistan',
    'Philippines',
    'Poland',
    'Portugal',
    'Russia',
    'Saudi Arabia',
    'Singapore',
    'South Africa',
    'South Korea',
    'Spain',
    'Sweden',
    'Switzerland',
    'Thailand',
    'Turkey',
    'Ukraine',
    'United Arab Emirates',
    'United Kingdom',
    'United States',
    'Vietnam',
];

// Filtered countries based on search
const filteredCountries = computed(() => {
    if (!countrySearch.value) return countries;
    const search = countrySearch.value.toLowerCase();
    return countries.filter((country) => country.toLowerCase().includes(search));
});

const currencyExamples = [
    { value: '$', symbol: '$', code: 'USD', name: 'US Dollar', search: 'usd dollar' },
    { value: '€', symbol: '€', code: 'EUR', name: 'Euro', search: 'eur euro' },
    { value: '£', symbol: '£', code: 'GBP', name: 'British Pound', search: 'gbp pound sterling' },
    { value: '¥', symbol: '¥', code: 'JPY', name: 'Japanese Yen / Chinese Yuan', search: 'jpy cny yen yuan' },
    { value: '₹', symbol: '₹', code: 'INR', name: 'Indian Rupee', search: 'inr rupee' },
    { value: 'R$', symbol: 'R$', code: 'BRL', name: 'Brazilian Real', search: 'brl real' },
    { value: '₽', symbol: '₽', code: 'RUB', name: 'Russian Ruble', search: 'rub ruble' },
    { value: 'A$', symbol: 'A$', code: 'AUD', name: 'Australian Dollar', search: 'aud australian' },
    { value: 'C$', symbol: 'C$', code: 'CAD', name: 'Canadian Dollar', search: 'cad canadian' },
    { value: '₱', symbol: '₱', code: 'PHP', name: 'Philippine Peso', search: 'php peso' },
    { value: '₩', symbol: '₩', code: 'KRW', name: 'South Korean Won', search: 'krw won' },
    { value: 'CHF', symbol: 'CHF', code: 'CHF', name: 'Swiss Franc', search: 'chf franc' },
    { value: 'kr', symbol: 'kr', code: 'SEK', name: 'Swedish Krona', search: 'sek krona' },
];

const isCurrencySelected = (currency: { value: string; symbol: string; code: string }): boolean => {
    const selectedValue = String(form.currency_symbol || '').trim();
    const selectedUpper = selectedValue.toUpperCase();

    return selectedValue === currency.value || selectedValue === currency.symbol || selectedUpper === currency.code;
};

// Filtered currencies based on search
const filteredCurrencies = computed(() => {
    if (!currencySearch.value) return currencyExamples;
    const search = currencySearch.value.toLowerCase();
    return currencyExamples.filter(
        (currency) =>
            currency.value.toLowerCase().includes(search) ||
            currency.symbol.toLowerCase().includes(search) ||
            currency.code.toLowerCase().includes(search) ||
            currency.name.toLowerCase().includes(search) ||
            currency.search.toLowerCase().includes(search),
    );
});

const visibleCurrencies = computed(() => {
    const selected = filteredCurrencies.value.find((currency) => isCurrencySelected(currency));
    if (!selected) {
        return filteredCurrencies.value;
    }

    return [selected, ...filteredCurrencies.value.filter((currency) => currency !== selected)];
});

const visibleTimezones = computed(() => {
    const selected = filteredTimezones.value.find((timezone) => timezone === form.timezone);
    if (!selected) {
        return filteredTimezones.value;
    }

    return [selected, ...filteredTimezones.value.filter((timezone) => timezone !== selected)];
});

const visibleCountries = computed(() => {
    const selected = filteredCountries.value.find((country) => country === form.country);
    if (!selected) {
        return filteredCountries.value;
    }

    return [selected, ...filteredCountries.value.filter((country) => country !== selected)];
});
</script>

<template>
    <AdminLayout>
        <div class="mx-auto max-w-4xl">
            <!-- Header -->
            <div class="mb-6">
                <div class="mb-2 flex items-center gap-3">
                    <div class="rounded-lg bg-primary-50 p-2 dark:bg-primary-900/20">
                        <Settings class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ __('Business Logic') }}
                    </h1>
                </div>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ __('Configure your business currency, timezone, and location settings.') }}
                </p>
            </div>

            <!-- Settings Form -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <form @submit.prevent="submit" class="space-y-6 p-6">
                    <!-- Currency Symbol with Search -->
                    <div>
                        <label for="currency_symbol" class="mb-2 flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <DollarSign class="h-4 w-4" />
                            {{ __('Currency Symbol') }}
                        </label>

                        <!-- Search Input -->
                        <div class="relative mb-3">
                            <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <input
                                v-model="currencySearch"
                                type="text"
                                placeholder="Search currency (e.g., $, Euro, Rupee)..."
                                class="w-full rounded-lg border border-gray-300 py-2.5 pr-4 pl-10 text-sm focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Currency Grid -->
                        <div
                            class="max-h-64 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-700/50"
                        >
                            <div v-if="visibleCurrencies.length > 0" class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <button
                                    v-for="currency in visibleCurrencies"
                                    :key="currency.value"
                                    type="button"
                                    @click="
                                        form.currency_symbol = currency.value;
                                        currencySearch = '';
                                    "
                                    class="flex items-center gap-3 rounded-lg border bg-white px-4 py-3 text-left transition-all dark:bg-gray-800"
                                    :class="
                                        isCurrencySelected(currency)
                                            ? 'border-primary-500 bg-primary-50 shadow-md dark:bg-primary-900/20'
                                            : 'border-gray-200 hover:border-primary-300 hover:bg-primary-50/50 dark:border-gray-600 dark:hover:border-primary-700 dark:hover:bg-primary-900/10'
                                    "
                                >
                                    <span class="min-w-[40px] text-2xl font-bold text-primary-600 dark:text-primary-400">{{ currency.symbol }}</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                            {{ currency.name }} ({{ currency.symbol }})
                                        </div>
                                    </div>
                                </button>
                            </div>
                            <div v-else class="py-8 text-center text-gray-500 dark:text-gray-400">
                                <Search class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                <p class="text-sm">No currencies found</p>
                            </div>
                        </div>

                        <p v-if="form.errors.currency_symbol" class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.currency_symbol }}
                        </p>
                    </div>

                    <!-- Timezone with Search and Time Display -->
                    <div>
                        <label for="timezone" class="mb-2 flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <Globe class="h-4 w-4" />
                            {{ __('Timezone') }}
                        </label>

                        <!-- Search Input -->
                        <div class="relative mb-3">
                            <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <input
                                v-model="timezoneSearch"
                                type="text"
                                placeholder="Search timezone (e.g., Dhaka, New York, Tokyo)..."
                                class="w-full rounded-lg border border-gray-300 py-2.5 pr-4 pl-10 text-sm focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Timezone List -->
                        <div class="max-h-80 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700/50">
                            <div v-if="visibleTimezones.length > 0" class="divide-y divide-gray-200 dark:divide-gray-600">
                                <button
                                    v-for="tz in visibleTimezones"
                                    :key="tz"
                                    type="button"
                                    @click="
                                        form.timezone = tz;
                                        timezoneSearch = '';
                                    "
                                    class="flex w-full items-center justify-between px-4 py-3 text-left transition-colors"
                                    :class="
                                        form.timezone === tz
                                            ? 'border-l-4 border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                            : 'hover:bg-white dark:hover:bg-gray-800'
                                    "
                                >
                                    <div class="min-w-0 flex-1 pr-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ tz }}</div>
                                        <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ getTimezoneInfo(tz).offset }}</div>
                                    </div>
                                    <div
                                        class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-1.5 font-mono text-xs whitespace-nowrap text-gray-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400"
                                    >
                                        <Clock class="h-3 w-3" />
                                        {{ getTimezoneInfo(tz).time }}
                                    </div>
                                </button>
                            </div>
                            <div v-else class="py-8 text-center text-gray-500 dark:text-gray-400">
                                <Search class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                <p class="text-sm">No timezones found</p>
                            </div>
                        </div>

                        <p v-if="form.errors.timezone" class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.timezone }}
                        </p>
                    </div>

                    <!-- Country with Search -->
                    <div>
                        <label for="country" class="mb-2 flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <MapPin class="h-4 w-4" />
                            {{ __('Country') }}
                        </label>

                        <!-- Search Input -->
                        <div class="relative mb-3">
                            <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <input
                                v-model="countrySearch"
                                type="text"
                                placeholder="Search country..."
                                class="w-full rounded-lg border border-gray-300 py-2.5 pr-4 pl-10 text-sm focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Country List -->
                        <div class="max-h-64 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700/50">
                            <div v-if="visibleCountries.length > 0" class="divide-y divide-gray-200 dark:divide-gray-600">
                                <button
                                    v-for="country in visibleCountries"
                                    :key="country"
                                    type="button"
                                    @click="
                                        form.country = country;
                                        countrySearch = '';
                                    "
                                    class="w-full px-4 py-3 text-left text-sm transition-colors"
                                    :class="
                                        form.country === country
                                            ? 'border-l-4 border-primary-500 bg-primary-50 font-semibold text-primary-700 dark:bg-primary-900/20 dark:text-primary-300'
                                            : 'text-gray-700 hover:bg-white dark:text-gray-300 dark:hover:bg-gray-800'
                                    "
                                >
                                    {{ country }}
                                </button>
                            </div>
                            <div v-else class="py-8 text-center text-gray-500 dark:text-gray-400">
                                <Search class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                <p class="text-sm">No countries found</p>
                            </div>
                        </div>

                        <p v-if="form.errors.country" class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.country }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                     <div class="flex justify-end border-t border-gray-200 pt-4 dark:border-gray-700">
                        <button
                            type="submit"
                            :disabled="form.processing || ($page.props.is_demo_mode as boolean)"
                            class="flex items-center gap-2 rounded-lg bg-primary-600 px-6 py-2.5 font-medium text-white shadow-lg shadow-primary-500/30 transition-colors hover:bg-primary-700 disabled:bg-gray-400 disabled:shadow-none"
                        >
                            <Settings class="h-4 w-4" :class="{ 'animate-spin': form.processing }" />
                            {{ form.processing ? __('Saving...') : ($page.props.is_demo_mode as boolean) ? __('Save Disabled') : __('Save Settings') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
