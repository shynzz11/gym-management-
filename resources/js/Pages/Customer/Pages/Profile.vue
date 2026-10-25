<script setup lang="ts">
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import customer from '@/routes/customer';
import { composePhoneFromDialCode, COUNTRY_PHONE_CODES, splitPhoneByDialCode } from '@/lib/countryPhoneCodes';
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{
    user: any;
}>();

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

const user = props.user ?? {};
const customerProfile = user.customer_profile ?? user.customerProfile ?? {};
const initialPhone = customerProfile.phone ?? '';
const defaultCountryIso = 'US';
const initialPhoneParts = splitPhoneByDialCode(initialPhone, defaultCountryIso);
const countryCodeOptions = COUNTRY_PHONE_CODES;

const preview = ref(user.profile_image ? `/storage/${user.profile_image}` : null);
const fileInput = ref<HTMLInputElement | null>(null);
const phoneCountryIso = ref(initialPhoneParts.countryIso);
const phoneLocalNumber = ref(initialPhoneParts.localNumber);
const phoneTouched = ref(false);
const attemptedSubmit = ref(false);
const isCountryMenuOpen = ref(false);
const countrySearch = ref('');
const countryPickerRef = ref<HTMLElement | null>(null);
const countrySearchInputRef = ref<HTMLInputElement | null>(null);
const countryMenuSide = ref<'top' | 'bottom'>('bottom');
const countryMenuMaxHeight = ref(224);
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    _method: 'PUT',
    name: user.name ?? '',
    email: user.email ?? '',
    phone: initialPhone,
    address: customerProfile.address ?? '',
    current_password: '',
    password: '',
    password_confirmation: '',
    profile_image: null as File | null,
});

const selectedCountryOption = computed(() => {
    return countryCodeOptions.find((option) => option.iso === phoneCountryIso.value) ?? countryCodeOptions[0];
});

const activePhoneRule = computed(() => {
    return countryPhoneRules[selectedCountryOption.value.iso] ?? null;
});

const normalizedPhoneDigits = computed(() => {
    return phoneLocalNumber.value.replace(/\D/g, '');
});

const sanitizedPhoneDigits = computed(() => {
    const digits = normalizedPhoneDigits.value;

    if (activePhoneRule.value?.stripLeadingZero) {
        return digits.replace(/^0+/, '');
    }

    return digits;
});

const isPhoneLengthValid = computed(() => {
    const digits = normalizedPhoneDigits.value;

    if (digits.length === 0) {
        return false;
    }

    if (activePhoneRule.value) {
        return digits.length >= activePhoneRule.value.min && digits.length <= activePhoneRule.value.max;
    }

    return digits.length >= 6 && digits.length <= 14;
});

const isPhonePatternValid = computed(() => {
    if (!activePhoneRule.value?.pattern) {
        return true;
    }

    return activePhoneRule.value.pattern.test(normalizedPhoneDigits.value);
});

const isPhoneValid = computed(() => {
    return isPhoneLengthValid.value && isPhonePatternValid.value;
});

const shouldShowPhoneValidationError = computed(() => {
    return (phoneTouched.value || attemptedSubmit.value) && !isPhoneValid.value;
});

const phoneValidationMessage = computed(() => {
    if (normalizedPhoneDigits.value.length === 0) {
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

const onFileChange = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    if (!target.files || !target.files[0]) {
        return;
    }

    const file = target.files[0];
    form.profile_image = file;
    preview.value = URL.createObjectURL(file);
};

const triggerFileInput = (): void => {
    fileInput.value?.click();
};

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

const submit = (): void => {
    attemptedSubmit.value = true;

    if (!isPhoneValid.value) {
        return;
    }

    form.transform((data) => ({
        ...data,
        phone: combinedPhoneNumber.value,
    })).post(customer.profile.update.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('current_password', 'password', 'password_confirmation');
            form.phone = combinedPhoneNumber.value;
            phoneTouched.value = false;
            attemptedSubmit.value = false;
        },
    });
};
</script>

<template>
    <CustomerLayout>
        <div class="animate-fade-in mx-auto max-w-4xl space-y-6">
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('Profile Settings') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Update your account information and password.') }}</p>
            </div>

            <div class="grid gap-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="mb-6 text-base font-bold text-slate-900 dark:text-white">{{ __('Personal Information') }}</h3>

                    <form class="space-y-6" @submit.prevent="submit">
                        <div class="flex items-center space-x-6">
                            <div class="group relative">
                                <div class="h-20 w-20 overflow-hidden rounded-full border-4 border-slate-100 shadow-md dark:border-slate-800">
                                    <img
                                        :src="preview || `https://ui-avatars.com/api/?name=${encodeURIComponent(form.name)}&background=random`"
                                        class="h-full w-full object-cover"
                                        alt="Profile"
                                    />
                                </div>
                                <div
                                    class="absolute inset-0 flex cursor-pointer items-center justify-center rounded-full bg-black/40 text-white opacity-0 transition-opacity group-hover:opacity-100"
                                    @click="triggerFileInput"
                                >
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                                        ></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white">{{ __('Profile Photo') }}</h4>
                                <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">{{ __('Accepts JPG, PNG or GIF. Max 2MB.') }}</p>
                                <button type="button" class="text-xs font-bold text-[var(--brand-primary)] hover:underline" @click="triggerFileInput">
                                    {{ __('Change Photo') }}
                                </button>
                                <input ref="fileInput" type="file" class="hidden" accept="image/*" @change="onFileChange" />
                                <div v-if="form.errors.profile_image" class="mt-1 text-xs text-red-500">{{ form.errors.profile_image }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-1">
                                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Full Name') }}</label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 transition-all outline-none focus:border-transparent focus:ring-2 focus:ring-[var(--brand-primary)] dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                    :placeholder="__('Your Name')"
                                />
                                <div v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</div>
                            </div>

                            <div class="space-y-1">
                                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Email Address') }}</label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 transition-all outline-none focus:border-transparent focus:ring-2 focus:ring-[var(--brand-primary)] dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                    :placeholder="__('name@example.com')"
                                />
                                <div v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Phone') }}</label>
                                <div
                                    class="flex rounded-xl border bg-white text-sm text-slate-900 transition-all focus-within:ring-2 focus-within:ring-[var(--brand-primary)]/20 dark:bg-slate-800 dark:text-white"
                                    :class="shouldShowPhoneValidationError ? 'border-red-300' : 'border-slate-300 dark:border-slate-700'"
                                >
                                    <div ref="countryPickerRef" class="relative min-w-[132px] border-e border-slate-300 dark:border-slate-700">
                                        <button type="button" class="flex w-full items-center justify-between px-3 py-2.5 outline-none" @click="toggleCountryMenu">
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
                                                    class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 ring-[var(--brand-primary)] outline-none focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
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
                                        class="w-full rounded-e-xl bg-transparent px-4 py-2.5 font-medium outline-none placeholder:text-slate-400 dark:placeholder:text-slate-500"
                                        :placeholder="__('Enter phone number')"
                                        @input="normalizePhoneInput"
                                        @blur="phoneTouched = true"
                                    />
                                </div>
                                <p v-if="shouldShowPhoneValidationError" class="text-xs text-red-500">{{ __(phoneValidationMessage) }}</p>
                                <p v-if="form.errors.phone" class="text-xs text-red-500">{{ form.errors.phone }}</p>
                            </div>

                            <div class="space-y-1">
                                <label for="address" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Address') }}</label>
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    rows="1"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 transition-all outline-none focus:border-transparent focus:ring-2 focus:ring-[var(--brand-primary)] dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                    :placeholder="__('Enter your address')"
                                ></textarea>
                                <div v-if="form.errors.address" class="text-xs text-red-500">{{ form.errors.address }}</div>
                            </div>
                        </div>

                        <div class="my-6 border-t border-slate-100 pt-6 dark:border-slate-800">
                            <h3 class="mb-4 text-base font-bold text-slate-900 dark:text-white">{{ __('Change Password') }}</h3>
                            <div class="max-w-md space-y-4">
                                <div class="space-y-1">
                                    <label for="current_password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Current Password') }}</label>
                                    <div class="relative">
                                        <input
                                            id="current_password"
                                            v-model="form.current_password"
                                            :type="showCurrentPassword ? 'text' : 'password'"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 pr-10 text-slate-900 transition-all outline-none focus:border-transparent focus:ring-2 focus:ring-[var(--brand-primary)] dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                            :placeholder="__('Enter current password')"
                                        />
                                        <button
                                            type="button"
                                            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                            @click="showCurrentPassword = !showCurrentPassword"
                                        >
                                            <svg v-if="!showCurrentPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    <div v-if="form.errors.current_password" class="text-xs text-red-500">{{ form.errors.current_password }}</div>
                                </div>

                                <div class="space-y-1">
                                    <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('New Password') }}</label>
                                    <div class="relative">
                                        <input
                                            id="password"
                                            v-model="form.password"
                                            :type="showNewPassword ? 'text' : 'password'"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 pr-10 text-slate-900 transition-all outline-none focus:border-transparent focus:ring-2 focus:ring-[var(--brand-primary)] dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                            :placeholder="__('Enter new password')"
                                        />
                                        <button
                                            type="button"
                                            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                            @click="showNewPassword = !showNewPassword"
                                        >
                                            <svg v-if="!showNewPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    <div v-if="form.errors.password" class="text-xs text-red-500">{{ form.errors.password }}</div>
                                </div>

                                <div class="space-y-1">
                                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {{ __('Confirm Password') }}
                                    </label>
                                    <div class="relative">
                                        <input
                                            id="password_confirmation"
                                            v-model="form.password_confirmation"
                                            :type="showConfirmPassword ? 'text' : 'password'"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 pr-10 text-slate-900 transition-all outline-none focus:border-transparent focus:ring-2 focus:ring-[var(--brand-primary)] dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                            :placeholder="__('Confirm new password')"
                                        />
                                        <button
                                            type="button"
                                            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                            @click="showConfirmPassword = !showConfirmPassword"
                                        >
                                            <svg v-if="!showConfirmPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex items-center space-x-2 rounded-xl bg-[var(--brand-primary)] px-4 py-2 text-sm font-bold text-white shadow-lg transition-all hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg
                                    v-if="form.processing"
                                    class="mr-2 -ml-1 h-4 w-4 animate-spin text-white"
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
                                <span>{{ __('Save Changes') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.4s ease-out;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
