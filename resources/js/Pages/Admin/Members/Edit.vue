<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { ChevronRight } from 'lucide-vue-next';
import type { PropType} from 'vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { composePhoneFromDialCode, COUNTRY_PHONE_CODES, splitPhoneByDialCode } from '@/lib/countryPhoneCodes';
import admin from '@/routes/admin';

const props = defineProps({
    user: Object as PropType<any>,
    roles: Array as PropType<any[]>,
    activeSubscriptions: {
        type: Array as PropType<any[]>,
        default: () => [],
    },
    directoryType: {
        type: String as PropType<'staff' | 'customer'>,
        default: 'staff',
    },
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    role_id: props.user.role_id ?? null,
    phone: props.user.customer_profile?.phone ?? props.user.customerProfile?.phone ?? '',
    address: props.user.customer_profile?.address ?? props.user.customerProfile?.address ?? '',
    password: '',
    password_confirmation: '',
});
const countryCodeOptions = COUNTRY_PHONE_CODES;
const defaultCountryIso = 'US';
const initialPhoneParts = splitPhoneByDialCode(form.phone, defaultCountryIso);
const phoneCountryIso = ref(initialPhoneParts.countryIso);
const phoneLocalNumber = ref(initialPhoneParts.localNumber);
const isCountryMenuOpen = ref(false);
const countrySearch = ref('');
const countryPickerRef = ref<HTMLElement | null>(null);
const countrySearchInputRef = ref<HTMLInputElement | null>(null);
const countryMenuSide = ref<'top' | 'bottom'>('bottom');
const countryMenuMaxHeight = ref(224);
const phoneTouched = ref(false);
const attemptedSubmit = ref(false);

const isCustomerUser = computed(() => props.directoryType === 'customer' || props.user.type === 'customer');
const directoryIndexUrl = computed(() => (isCustomerUser.value ? admin.customers.index.url() : admin.staff.index.url()));
const updateUrl = computed(() => (isCustomerUser.value ? admin.customers.update.url(props.user.id) : admin.staff.update.url(props.user.id)));
const currentPlans = computed(() => props.activeSubscriptions ?? []);

const showPasswordFields = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const selectedCountryOption = computed(() => {
    return countryCodeOptions.find((option) => option.iso === phoneCountryIso.value) ?? countryCodeOptions[0];
});

interface CountryPhoneRule {
    min: number;
    max: number;
    pattern?: RegExp;
    stripLeadingZero?: boolean;
    hint: string;
}

const countryPhoneRules: Record<string, CountryPhoneRule> = {
    US: {
        min: 10,
        max: 10,
        pattern: /^[2-9]\d{9}$/,
        hint: 'Enter a valid 10-digit US phone number.',
    },
    CA: {
        min: 10,
        max: 10,
        pattern: /^[2-9]\d{9}$/,
        hint: 'Enter a valid 10-digit Canada phone number.',
    },
    BD: {
        min: 10,
        max: 11,
        pattern: /^0?1[3-9]\d{8}$/,
        stripLeadingZero: true,
        hint: 'Enter a valid Bangladesh mobile number.',
    },
    IN: {
        min: 10,
        max: 11,
        pattern: /^0?[6-9]\d{9}$/,
        stripLeadingZero: true,
        hint: 'Enter a valid 10-digit India mobile number.',
    },
    PK: {
        min: 10,
        max: 11,
        pattern: /^0?3\d{9}$/,
        stripLeadingZero: true,
        hint: 'Enter a valid Pakistan mobile number.',
    },
    GB: {
        min: 10,
        max: 11,
        pattern: /^0?\d{10}$/,
        stripLeadingZero: true,
        hint: 'Enter a valid UK phone number.',
    },
    AE: {
        min: 9,
        max: 10,
        pattern: /^0?5\d{8}$/,
        stripLeadingZero: true,
        hint: 'Enter a valid UAE mobile number.',
    },
    SA: {
        min: 9,
        max: 10,
        pattern: /^0?5\d{8}$/,
        stripLeadingZero: true,
        hint: 'Enter a valid Saudi Arabia mobile number.',
    },
    AU: {
        min: 9,
        max: 10,
        pattern: /^0?[2-478]\d{8}$/,
        stripLeadingZero: true,
        hint: 'Enter a valid Australia phone number.',
    },
};

const activePhoneRule = computed(() => {
    return countryPhoneRules[selectedCountryOption.value.iso] ?? null;
});

const normalizedPhoneDigits = computed(() => phoneLocalNumber.value.replace(/\D/g, ''));

const sanitizedPhoneDigits = computed(() => {
    const digits = normalizedPhoneDigits.value;

    if (activePhoneRule.value?.stripLeadingZero) {
        return digits.replace(/^0+/, '');
    }

    return digits;
});

const hasPhoneInput = computed(() => normalizedPhoneDigits.value.length > 0);

const isPhoneLengthValid = computed(() => {
    if (!hasPhoneInput.value) {
        return true;
    }

    const digits = normalizedPhoneDigits.value;

    if (activePhoneRule.value) {
        return digits.length >= activePhoneRule.value.min && digits.length <= activePhoneRule.value.max;
    }

    return digits.length >= 6 && digits.length <= 14;
});

const isPhonePatternValid = computed(() => {
    if (!hasPhoneInput.value) {
        return true;
    }

    if (!activePhoneRule.value?.pattern) {
        return true;
    }

    return activePhoneRule.value.pattern.test(normalizedPhoneDigits.value);
});

const isPhoneValid = computed(() => {
    if (!isCustomerUser.value) {
        return true;
    }

    return hasPhoneInput.value && isPhoneLengthValid.value && isPhonePatternValid.value;
});

const shouldShowPhoneValidationError = computed(() => {
    if (!isCustomerUser.value) {
        return false;
    }

    return (phoneTouched.value || attemptedSubmit.value) && !isPhoneValid.value;
});

const phoneValidationMessage = computed(() => {
    if (!hasPhoneInput.value) {
        return 'Phone number is required.';
    }

    if (activePhoneRule.value && (!isPhonePatternValid.value || !isPhoneLengthValid.value)) {
        return activePhoneRule.value.hint;
    }

    return 'Enter a valid phone number (6-14 digits).';
});

const combinedPhoneNumber = computed(() => {
    return composePhoneFromDialCode(selectedCountryOption.value.dialCode, sanitizedPhoneDigits.value);
});

const filteredCountryOptions = computed(() => {
    const term = countrySearch.value.trim().toLowerCase();

    if (!term) {
        return countryCodeOptions;
    }

    const dialTerm = term.replace(/[^+\d]/g, '');

    return countryCodeOptions.filter((option) => {
        const matchesDialCode = dialTerm.length > 0 && option.dialCode.includes(dialTerm);

        return option.name.toLowerCase().includes(term) || option.iso.toLowerCase().includes(term) || matchesDialCode;
    });
});

const normalizePhoneInput = (): void => {
    phoneTouched.value = true;
    phoneLocalNumber.value = phoneLocalNumber.value.replace(/\D/g, '').slice(0, 14);
};

const setCountryMenuPlacement = (): void => {
    if (!countryPickerRef.value) {
        return;
    }

    const rect = countryPickerRef.value.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const reservedSpace = 24;
    const minimumVisibleSpace = 180;
    const searchBlockHeight = 56;
    const spaceBelow = viewportHeight - rect.bottom - reservedSpace;
    const spaceAbove = rect.top - reservedSpace;
    const shouldOpenTop = spaceBelow < minimumVisibleSpace && spaceAbove > spaceBelow;
    const usableSpace = shouldOpenTop ? spaceAbove : spaceBelow;

    countryMenuSide.value = shouldOpenTop ? 'top' : 'bottom';
    countryMenuMaxHeight.value = Math.max(140, Math.min(320, Math.floor(usableSpace - searchBlockHeight)));
};

const focusCountrySearchInput = (): void => {
    nextTick(() => {
        countrySearchInputRef.value?.focus();
        countrySearchInputRef.value?.select();
    });
};

const toggleCountryMenu = (): void => {
    isCountryMenuOpen.value = !isCountryMenuOpen.value;

    if (isCountryMenuOpen.value) {
        countrySearch.value = '';
        setCountryMenuPlacement();
        focusCountrySearchInput();
    }
};

const selectCountryCode = (iso: string): void => {
    phoneCountryIso.value = iso;
    isCountryMenuOpen.value = false;
    countrySearch.value = '';
};

const handleOutsideCountryMenuClick = (event: MouseEvent): void => {
    const target = event.target as Node;

    if (!countryPickerRef.value?.contains(target)) {
        isCountryMenuOpen.value = false;
    }
};

const handleViewportResize = (): void => {
    if (isCountryMenuOpen.value) {
        setCountryMenuPlacement();
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleOutsideCountryMenuClick);
    window.addEventListener('resize', handleViewportResize);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleOutsideCountryMenuClick);
    window.removeEventListener('resize', handleViewportResize);
});

const submit = () => {
    attemptedSubmit.value = true;

    if (isCustomerUser.value && !isPhoneValid.value) {
        return;
    }

    form
        .transform((data) => {
            if (isCustomerUser.value) {
                return {
                    name: data.name,
                    email: data.email,
                    address: data.address,
                    phone: combinedPhoneNumber.value,
                    role_id: null,
                };
            }

            return data;
        })
        .put(updateUrl.value, {
            onSuccess: () => {
                form.reset('password', 'password_confirmation');
                showPasswordFields.value = false;
                phoneTouched.value = false;
                attemptedSubmit.value = false;
            },
        });
};

const formatPlanDate = (date: string): string => {
    return date ? dayjs(date).format('MMM D, YYYY') : '-';
};

</script>

<template>
    <Head :title="__('Edit User Data')" />

    <AdminLayout>
        <div class="animate-fade-in mx-auto max-w-2xl space-y-6">
            <div
                class="flex flex-col items-start justify-between gap-6 border-b border-slate-200 pb-6 sm:flex-row sm:items-center dark:border-slate-800"
            >
                <div>
                    <div class="mb-2 flex items-center gap-2 text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        <Link :href="directoryIndexUrl" class="hover:text-brand-500">{{ isCustomerUser ? __('Customer List') : __('Staff List') }}</Link>
                        <ChevronRight :size="8" class="opacity-40 rtl:rotate-180" />
                        <span class="text-brand-500">{{ isCustomerUser ? __('Edit Customer') : __('Edit Staff') }}</span>
                    </div>
                    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                        {{ isCustomerUser ? __('Edit Customer Data') : __('Edit User Data') }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ isCustomerUser ? __('Update customer profile details.') : __('Update staff details and access role.') }}
                    </p>
                </div>
            </div>

            <div class="rounded-[2rem] border border-slate-200/60 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="mb-2 block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('Full Name') }}</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 font-medium text-slate-900 transition-all outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-900/50 dark:text-white dark:placeholder:text-slate-500"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('Email Address') }}</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 font-medium text-slate-900 transition-all outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-900/50 dark:text-white dark:placeholder:text-slate-500"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <div v-if="!isCustomerUser">
                        <label class="mb-2 block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('Access Role') }}</label>
                        <select
                            v-model="form.role_id"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 font-medium text-slate-900 transition-all outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-900/50 dark:text-white"
                        >
                            <option value="" disabled>{{ __('Select Role') }}</option>
                            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                        </select>
                        <p v-if="form.errors.role_id" class="mt-1 text-xs text-red-500">{{ form.errors.role_id }}</p>
                    </div>

                    <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('Phone') }}</label>
                            <div
                                class="flex rounded-2xl border bg-slate-50 text-sm text-slate-900 transition-all focus-within:ring-4 dark:bg-slate-900/50 dark:text-white"
                                :class="
                                    shouldShowPhoneValidationError
                                        ? 'border-red-300 focus-within:border-red-400 focus-within:ring-red-500/10'
                                        : 'border-slate-200 focus-within:border-brand-500 focus-within:ring-brand-500/10 dark:border-slate-700'
                                "
                            >
                                <div ref="countryPickerRef" class="relative min-w-[132px] border-e border-slate-300 dark:border-slate-700">
                                    <button type="button" class="flex w-full items-center justify-between px-3 py-3 outline-none" @click="toggleCountryMenu">
                                        <span class="inline-flex items-center gap-2">
                                            <span>{{ selectedCountryOption.flag }}</span>
                                            <span>{{ selectedCountryOption.dialCode }}</span>
                                        </span>
                                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    <div
                                        v-if="isCountryMenuOpen"
                                        class="absolute left-0 z-30 w-[min(22rem,calc(100vw-3rem))] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
                                        :class="countryMenuSide === 'top' ? 'bottom-full mb-1' : 'top-full mt-1'"
                                    >
                                        <div class="border-b border-slate-200 p-2 dark:border-slate-700">
                                            <input
                                                ref="countrySearchInputRef"
                                                v-model="countrySearch"
                                                type="text"
                                                :placeholder="__('Search country or code')"
                                                class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 ring-brand-500 outline-none focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                            />
                                        </div>

                                        <div class="overflow-y-auto py-1" :style="{ maxHeight: `${countryMenuMaxHeight}px` }">
                                            <button
                                                v-for="option in filteredCountryOptions"
                                                :key="`${option.iso}-${option.dialCode}`"
                                                type="button"
                                                class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-800"
                                                @click="selectCountryCode(option.iso)"
                                            >
                                                <span class="inline-flex items-center gap-2">
                                                    <span>{{ option.flag }}</span>
                                                    <span class="truncate">{{ option.name }}</span>
                                                </span>
                                                <span class="text-slate-500">{{ option.dialCode }}</span>
                                            </button>

                                            <p v-if="filteredCountryOptions.length === 0" class="px-3 py-2 text-sm text-slate-500">
                                                {{ __('No country found') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <input
                                    v-model="phoneLocalNumber"
                                    type="tel"
                                    inputmode="tel"
                                    required
                                    class="w-full rounded-e-2xl bg-transparent px-4 py-3 font-medium outline-none placeholder:text-slate-400 dark:placeholder:text-slate-500"
                                    :placeholder="__('Enter phone number')"
                                    @input="normalizePhoneInput"
                                />
                            </div>
                            <p v-if="shouldShowPhoneValidationError" class="mt-1 text-xs text-red-500">{{ phoneValidationMessage }}</p>
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('Address') }}</label>
                            <input
                                v-model="form.address"
                                type="text"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 font-medium text-slate-900 transition-all outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-900/50 dark:text-white dark:placeholder:text-slate-500"
                            />
                            <p v-if="form.errors.address" class="mt-1 text-xs text-red-500">{{ form.errors.address }}</p>
                        </div>
                    </div>

                    <div v-if="isCustomerUser" class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Customer Plans') }}</h3>
                            <span class="rounded-full bg-brand-100 px-2 py-1 text-[10px] font-bold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                {{ `${currentPlans.length} ${__('plans')}` }}
                            </span>
                        </div>

                        <div v-if="currentPlans.length" class="space-y-2">
                            <div
                                v-for="subscription in currentPlans"
                                :key="subscription.id"
                                class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ subscription.plan?.name ?? __('Unknown Plan') }}</p>
                                    <span
                                        class="rounded-full px-2 py-1 text-[10px] font-bold uppercase"
                                        :class="
                                            subscription.status === 'active'
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
                                        "
                                    >
                                        {{ subscription.status }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ __('Access') }}: {{ subscription.plan?.access_type ?? __('Anytime') }}
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ __('Starts') }}: {{ formatPlanDate(subscription.start_date) }} • {{ __('Expires') }}: {{ formatPlanDate(subscription.end_date) }}
                                </p>
                            </div>
                        </div>
                        <p v-else class="text-xs text-slate-500 dark:text-slate-400">{{ __('No active or pending plans assigned.') }}</p>
                    </div>

                    <!-- Password Update Section -->
                    <div v-if="!isCustomerUser" class="border-t border-slate-200 pt-6 dark:border-slate-700">
                        <button
                            type="button"
                            @click="showPasswordFields = !showPasswordFields"
                            class="flex items-center gap-2 text-sm font-bold text-brand-600 transition-colors hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300"
                        >
                            <span v-if="!showPasswordFields">{{ __('Change Password') }}</span>
                            <span v-else>{{ __('Cancel Password Change') }}</span>
                        </button>

                        <div v-if="showPasswordFields" class="mt-4 space-y-4">
                            <div>
                                <label class="mb-2 block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('New Password') }}</label>
                                <div class="relative">
                                    <input
                                        v-model="form.password"
                                        :type="showNewPassword ? 'text' : 'password'"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 pr-10 font-medium text-slate-900 transition-all outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-900/50 dark:text-white dark:placeholder:text-slate-500"
                                        placeholder="Enter new password"
                                    />
                                    <button
                                        type="button"
                                        @click="showNewPassword = !showNewPassword"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                    >
                                        <svg v-if="!showNewPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            ></path>
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
                                <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-xs font-bold text-slate-600 dark:text-slate-400">{{
                                    __('Confirm New Password')
                                }}</label>
                                <div class="relative">
                                    <input
                                        v-model="form.password_confirmation"
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 pr-10 font-medium text-slate-900 transition-all outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-900/50 dark:text-white dark:placeholder:text-slate-500"
                                        placeholder="Confirm new password"
                                    />
                                    <button
                                        type="button"
                                        @click="showConfirmPassword = !showConfirmPassword"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                    >
                                        <svg v-if="!showConfirmPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            ></path>
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
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <Link
                            :href="directoryIndexUrl"
                            class="rounded-2xl border border-slate-200 px-6 py-3 text-center text-sm font-bold text-slate-500 transition-all hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800"
                        >
                            {{ __('Cancel') }}
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-2xl bg-brand-600 px-8 py-3 text-sm font-bold text-white shadow-xl shadow-brand-600/20 transition-all hover:bg-brand-700 disabled:opacity-50"
                        >
                            {{ form.processing ? __('Saving...') : __('Save Changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
