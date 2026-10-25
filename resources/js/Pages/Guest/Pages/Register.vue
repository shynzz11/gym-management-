<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { login, register } from '@/routes';
import { privacy, terms } from '@/routes/legal';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import Toast from '@/Components/Common/Toast.vue';
import PaymentSelectionModal from '@/Components/Common/PaymentSelectionModal.vue';
import { COUNTRY_PHONE_CODES } from '@/lib/countryPhoneCodes';
import useCurrency from '@/Composables/useCurrency';
import { Check, ChevronRight, ChevronLeft } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type PaymentGateway = 'sslcommerz' | 'stripe' | 'cash';

type PaymentOptions = {
    currency_code?: string;
    sslcommerz?: boolean;
    stripe?: boolean;
    cash?: boolean;
    default_gateway?: string;
};

const props = defineProps<{
    plans: Array<{
        id: number;
        name: string;
        price: string;
        duration_days: number;
        description: string | null;
        is_offer?: boolean;
        offer_price?: string;
        offer_ends_at?: string;
    }>;
    paymentOptions?: PaymentOptions;
}>();

const page = usePage();
const { formatCurrency } = useCurrency();
const businessSettings = computed(() => (page.props.branding as any)?.business_settings || {});
const brandName = computed(() => businessSettings.value.business_name || 'GymMaster');
const brandTagline = computed(() => businessSettings.value.tagline || 'Next-Gen Gym Management');
const logoUrl = computed(() => {
    const logo = businessSettings.value.logo_url;
    const defaultLogo = 'https://api.dicebear.com/7.x/initials/svg?seed=GymMaster&backgroundColor=0284c7';
    const src = logo || defaultLogo;

    return src.startsWith('http') ? src : '/storage/' + src;
});

const step = ref<1 | 2>(1);
const countryCodeOptions = COUNTRY_PHONE_CODES;
const phoneCountryIso = ref('US');
const phoneLocalNumber = ref('');
const isCountryMenuOpen = ref(false);
const countrySearch = ref('');
const countryPickerRef = ref<HTMLElement | null>(null);
const phoneTouched = ref(false);
const attemptedStepOneSubmit = ref(false);
const showPassword = ref(false);
const showConfirmPassword = ref(false);
const showPaymentModal = ref(false);

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

const form = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
    plan_id: '' as string | number,
    country_iso: '',
    payment_gateway: '',
    terms: false,
    password: '',
    password_confirmation: '',
});

const isPasswordMismatch = computed(() => {
    return form.password.length > 0 && form.password_confirmation.length > 0 && form.password !== form.password_confirmation;
});
const shouldShowPasswordMismatchError = computed(() => {
    return isPasswordMismatch.value;
});
const selectedCountryOption = computed(() => {
    return countryCodeOptions.find((option) => option.iso === phoneCountryIso.value) ?? countryCodeOptions[0];
});
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
const isPhoneLengthValid = computed(() => {
    const digits = normalizedPhoneDigits.value;
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
    return (phoneTouched.value || attemptedStepOneSubmit.value) && !isPhoneValid.value;
});
const phoneValidationMessage = computed(() => {
    if (normalizedPhoneDigits.value.length === 0) {
        return 'Phone number is required.';
    }

    if (activePhoneRule.value && !isPhonePatternValid.value) {
        return activePhoneRule.value.hint;
    }

    if (!isPhoneLengthValid.value && activePhoneRule.value) {
        return activePhoneRule.value.hint;
    }

    return 'Enter a valid phone number (6-14 digits).';
});
const combinedPhoneNumber = computed(() => `${selectedCountryOption.value.dialCode}${sanitizedPhoneDigits.value}`);
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

const canContinueStepOne = computed(() => {
    return (
        form.name.trim().length > 0 &&
        form.email.trim().length > 0 &&
        isPhoneValid.value &&
        form.address.trim().length > 0 &&
        form.password.length > 0 &&
        form.password_confirmation.length > 0 &&
        form.password === form.password_confirmation &&
        form.terms
    );
});

const selectedPlan = computed(() => {
    return props.plans.find((plan) => String(plan.id) === String(form.plan_id));
});

const isOfferActive = (plan: any): boolean => {
    if (!plan?.is_offer || !plan?.offer_ends_at) {
        return false;
    }

    return new Date(plan.offer_ends_at) > new Date();
};

const selectedPlanAmount = computed(() => {
    if (!selectedPlan.value) {
        return 0;
    }

    if (isOfferActive(selectedPlan.value)) {
        return Number(selectedPlan.value.offer_price);
    }

    return Number(selectedPlan.value.price);
});

const systemCurrencyCode = computed(() => {
    return String(props.paymentOptions?.currency_code ?? 'INR').trim().toUpperCase();
});
const availablePaymentGateways = computed<PaymentGateway[]>(() => {
    if (selectedPlanAmount.value <= 0) {
        return ['cash'];
    }

    const gateways: PaymentGateway[] = [];

    if (Boolean(props.paymentOptions?.sslcommerz)) {
        gateways.push('sslcommerz');
    }

    if (Boolean(props.paymentOptions?.stripe)) {
        gateways.push('stripe');
    }

    if (Boolean(props.paymentOptions?.cash ?? true)) {
        gateways.push('cash');
    }

    return gateways.length > 0 ? gateways : ['cash'];
});
const defaultGateway = computed<PaymentGateway>(() => {
    const configuredDefault = String(props.paymentOptions?.default_gateway ?? '') as PaymentGateway;
    if (availablePaymentGateways.value.includes(configuredDefault)) {
        return configuredDefault;
    }

    return availablePaymentGateways.value[0] ?? 'cash';
});

const canSubmit = computed(() => {
    return canContinueStepOne.value && !!form.plan_id;
});

const goNext = () => {
    attemptedStepOneSubmit.value = true;

    if (!canContinueStepOne.value) {
        return;
    }

    form.phone = combinedPhoneNumber.value;
    step.value = 2;
};

const goBack = () => {
    step.value = 1;
};

const submit = () => {
    if (!canSubmit.value) {
        return;
    }

    showPaymentModal.value = true;
};

const closePaymentModal = (): void => {
    showPaymentModal.value = false;
};

const completeRegistrationWithGateway = (gateway: PaymentGateway): void => {
    form.phone = combinedPhoneNumber.value;
    form.country_iso = phoneCountryIso.value;
    form.payment_gateway = gateway;
    form.post(register.url(), {
        preserveScroll: true,
        onFinish: () => {
            showPaymentModal.value = false;
        },
    });
};

const normalizePhoneInput = (): void => {
    phoneTouched.value = true;
    phoneLocalNumber.value = phoneLocalNumber.value.replace(/\D/g, '').slice(0, 14);
};

const toggleCountryMenu = (): void => {
    isCountryMenuOpen.value = !isCountryMenuOpen.value;
    if (isCountryMenuOpen.value) {
        countrySearch.value = '';
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

const applyPreselectedPlanFromUrl = (): void => {
    const pageUrl = String(page.url ?? '');
    const queryString = typeof window !== 'undefined'
        ? window.location.search
        : pageUrl.includes('?')
          ? `?${pageUrl.split('?')[1]}`
          : '';
    const params = new URLSearchParams(queryString);
    const preselectedPlanId = params.get('plan_id');

    if (!preselectedPlanId) {
        return;
    }

    const matchedPlan = props.plans.find((plan) => String(plan.id) === preselectedPlanId);
    if (!matchedPlan) {
        return;
    }

    form.plan_id = matchedPlan.id;
};

onMounted(() => {
    document.addEventListener('mousedown', handleOutsideCountryMenuClick);
    applyPreselectedPlanFromUrl();
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleOutsideCountryMenuClick);
});
</script>

<template>
    <Head :title="__('Register')" />
    <Toast />

    <div class="min-h-screen bg-slate-50 text-slate-900 transition-colors dark:bg-slate-950 dark:text-slate-100">
        <div class="mx-auto flex min-h-screen w-full max-w-7xl flex-col lg:flex-row">
            <div
                class="flex w-full flex-col justify-between border-b border-slate-200 bg-white p-8 lg:w-[42%] lg:border-r lg:border-b-0 lg:p-10 dark:border-slate-800 dark:bg-slate-950"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <Link href="/" class="group flex items-center gap-3">
                            <div
                                class="h-11 w-11 overflow-hidden rounded-xl border border-slate-200 bg-slate-100 p-1.5 dark:border-slate-700 dark:bg-slate-800"
                            >
                                <img :src="logoUrl" :alt="brandName" class="h-full w-full object-contain" />
                            </div>
                            <div class="leading-tight">
                                <p class="text-lg font-black tracking-tight text-slate-900 dark:text-white">{{ brandName }}</p>
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ brandTagline }}</p>
                            </div>
                        </Link>
                        <ThemeToggle />
                    </div>
                    <p class="mt-4 text-sm text-slate-600 dark:text-slate-400">{{ __('Your Fitness, Our Data.') }}</p>

                    <div class="mt-10 space-y-3">
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold"
                                :class="step >= 1 ? 'bg-primary-600 text-white' : 'bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-300'"
                                >1</span
                            >
                            <span :class="step >= 1 ? 'text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'">{{
                                __('Account Details')
                            }}</span>
                        </div>
                        <div class="ms-3 h-6 w-px bg-slate-300 dark:bg-slate-800"></div>
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold"
                                :class="step >= 2 ? 'bg-primary-600 text-white' : 'bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-300'"
                                >2</span
                            >
                            <span :class="step >= 2 ? 'text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'">{{
                                __('Choose Plan')
                            }}</span>
                        </div>
                        <div class="ms-3 h-6 w-px bg-slate-300 dark:bg-slate-800"></div>
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-500 dark:bg-slate-700 dark:text-slate-300"
                                >3</span
                            >
                            <span class="text-slate-500 dark:text-slate-400">{{ __('Success') }}</span>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-slate-200 bg-slate-100 p-4 text-xs text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400"
                >
                    {{ __('Complete registration in two steps. Your membership will remain pending until admin approval.') }}
                </div>
            </div>

            <div class="w-full bg-slate-100 p-6 lg:w-[58%] lg:p-10 dark:bg-slate-900">
                <form @submit.prevent="submit" class="mx-auto max-w-2xl space-y-6">
                    <div v-if="step === 1" class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-950">
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('Step 1: Account Details') }}</h1>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('All fields are required.') }}</p>

                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200"
                                    >{{ __('Full Name') }} <span class="text-rose-400">*</span></label
                                >
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 ring-primary-500 outline-none focus:ring-2 dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-rose-400">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200"
                                    >{{ __('Email') }} <span class="text-rose-400">*</span></label
                                >
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 ring-primary-500 outline-none focus:ring-2 dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-400">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200"
                                    >{{ __('Phone Number') }} <span class="text-rose-400">*</span></label
                                >
                                <div
                                    class="flex rounded-lg border border-slate-300 bg-white text-sm text-slate-900 ring-primary-500 focus-within:ring-2 dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                                >
                                    <div ref="countryPickerRef" class="relative min-w-[128px] border-e border-slate-300 dark:border-slate-700">
                                        <button
                                            type="button"
                                            class="flex w-full items-center justify-between px-2.5 py-2.5 outline-none"
                                            @click="toggleCountryMenu"
                                        >
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
                                            class="absolute top-full z-30 mt-1 w-72 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
                                        >
                                            <div class="border-b border-slate-200 p-2 dark:border-slate-700">
                                                <input
                                                    v-model="countrySearch"
                                                    type="text"
                                                    placeholder="Search country or code"
                                                    class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 ring-primary-500 outline-none focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                                />
                                            </div>

                                            <div class="max-h-56 overflow-y-auto py-1">
                                                <button
                                                    v-for="option in filteredCountryOptions"
                                                    :key="`${option.iso}-${option.dialCode}`"
                                                    type="button"
                                                    class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-800"
                                                    @click="selectCountryCode(option.iso)"
                                                >
                                                    <span class="inline-flex items-center gap-2">
                                                        <span>{{ option.flag }}</span>
                                                        <span class="text-slate-800 dark:text-slate-100">{{ option.name }}</span>
                                                    </span>
                                                    <span class="text-slate-500 dark:text-slate-400">{{ option.dialCode }}</span>
                                                </button>
                                                <p v-if="filteredCountryOptions.length === 0" class="px-3 py-3 text-xs text-slate-500">
                                                    {{ __('No country found') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <input
                                        v-model="phoneLocalNumber"
                                        type="tel"
                                        inputmode="numeric"
                                        maxlength="14"
                                        placeholder="2025550123"
                                        class="w-full rounded-e-lg bg-transparent px-3 py-2.5 text-sm outline-none"
                                        @input="normalizePhoneInput"
                                        @blur="phoneTouched = true"
                                    />
                                </div>
                                <p v-if="shouldShowPhoneValidationError" class="mt-1 text-xs text-rose-400">
                                    {{ __(phoneValidationMessage) }}
                                </p>
                                <p v-if="form.errors.phone" class="mt-1 text-xs text-rose-400">{{ form.errors.phone }}</p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200"
                                    >{{ __('Address') }} <span class="text-rose-400">*</span></label
                                >
                                <textarea
                                    v-model="form.address"
                                    rows="3"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 ring-primary-500 outline-none focus:ring-2 dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                                ></textarea>
                                <p v-if="form.errors.address" class="mt-1 text-xs text-rose-400">{{ form.errors.address }}</p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200"
                                    >{{ __('Password') }} <span class="text-rose-400">*</span></label
                                >
                                <div class="relative">
                                    <input
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pe-10 text-sm text-slate-900 ring-primary-500 outline-none focus:ring-2 dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 end-0 flex items-center px-3 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                                        @click="showPassword = !showPassword"
                                    >
                                        <svg
                                            v-if="!showPassword"
                                            class="h-5 w-5"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            class="h-5 w-5"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <p v-if="form.errors.password" class="mt-1 text-xs text-rose-400">{{ form.errors.password }}</p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200"
                                    >{{ __('Confirm Password') }} <span class="text-rose-400">*</span></label
                                >
                                <div class="relative">
                                    <input
                                        v-model="form.password_confirmation"
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pe-10 text-sm text-slate-900 ring-primary-500 outline-none focus:ring-2 dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 end-0 flex items-center px-3 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                                        @click="showConfirmPassword = !showConfirmPassword"
                                    >
                                        <svg
                                            v-if="!showConfirmPassword"
                                            class="h-5 w-5"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            class="h-5 w-5"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-rose-400">
                                    {{ form.errors.password_confirmation }}
                                </p>
                                <p v-if="shouldShowPasswordMismatchError" class="mt-1 text-xs text-rose-400">
                                    {{ __('Password and confirm password do not match.') }}
                                </p>
                            </div>
                        </div>

                        <label
                            class="mt-5 flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300"
                        >
                            <input
                                v-model="form.terms"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 rounded border-slate-400 bg-white text-primary-600 dark:border-slate-600 dark:bg-slate-800"
                            />
                            <span>
                                {{ __('I agree to the') }} <span class="text-rose-400">*</span>
                                <a
                                    :href="privacy.url()"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="font-semibold text-primary-400 hover:text-primary-300"
                                    >{{ __('Privacy Policy') }}</a
                                >
                                {{ __('and') }}
                                <a
                                    :href="terms.url()"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="font-semibold text-primary-400 hover:text-primary-300"
                                    >{{ __('Terms & Conditions') }}</a
                                >
                            </span>
                        </label>
                        <p v-if="form.errors.terms" class="mt-1 text-xs text-rose-400">{{ form.errors.terms }}</p>

                        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                {{ __('Already have an account?') }}
                                <Link :href="login.url()" class="font-semibold text-primary-400 hover:text-primary-300">{{ __('Sign In') }}</Link>
                            </p>
                            <button
                                type="button"
                                :disabled="!canContinueStepOne"
                                @click="goNext"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto"
                            >
                                {{ __('Next') }}
                                <ChevronRight class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="attemptedStepOneSubmit && !canContinueStepOne" class="mt-2 text-xs text-rose-400">
                            {{ __('Please fix the highlighted fields to continue.') }}
                        </p>
                    </div>

                    <div v-else class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-950">
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('Step 2: Choose Plan') }}</h1>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Select your membership package.') }}</p>

                        <div class="mt-6 grid gap-4">
                            <button
                                v-for="plan in plans"
                                :key="plan.id"
                                type="button"
                                @click="form.plan_id = plan.id"
                                class="rounded-xl border p-4 text-left transition-all"
                                :class="
                                    String(form.plan_id) === String(plan.id)
                                        ? 'border-primary-500 bg-primary-500/10'
                                        : 'border-slate-300 bg-white hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-500'
                                "
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-base font-semibold text-slate-900 dark:text-white">{{ plan.name }}</p>
                                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                            {{ plan.description || __('Membership package') }}
                                        </p>
                                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ plan.duration_days }} {{ __('days') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="flex flex-col items-end">
                                            <p 
                                                class="text-xl font-bold text-slate-900 dark:text-white"
                                                :class="{ 'text-sm opacity-50 line-through scale-90 origin-right -mb-1': isOfferActive(plan) }"
                                            >
                                                {{ formatCurrency(plan.price) }}
                                            </p>
                                            <p v-if="isOfferActive(plan)" class="text-xl font-bold text-orange-600 animate-in fade-in slide-in-from-right-2">
                                                {{ formatCurrency(plan.offer_price) }}
                                            </p>
                                        </div>
                                        <span
                                            v-if="String(form.plan_id) === String(plan.id)"
                                            class="mt-2 inline-flex items-center gap-1 rounded-md bg-emerald-600 px-2 py-1 text-xs font-semibold text-white"
                                        >
                                            <Check class="h-3 w-3" /> {{ __('Selected') }}
                                        </span>
                                    </div>
                                </div>
                            </button>
                        </div>
                        <p v-if="form.errors.plan_id" class="mt-2 text-xs text-rose-400">{{ form.errors.plan_id }}</p>

                        <div
                            v-if="selectedPlan"
                            class="mt-5 flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300"
                        >
                            <div>
                                {{ __('Selected Plan') }}: <span class="font-semibold text-slate-900 dark:text-white">{{ selectedPlan.name }}</span>
                            </div>
                            <div class="font-bold text-primary-600 dark:text-primary-400">
                                {{ formatCurrency(selectedPlanAmount) }}
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-between">
                            <button
                                type="button"
                                @click="goBack"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                            >
                                <ChevronLeft class="h-4 w-4" />
                                {{ __('Back') }}
                            </button>

                            <button
                                type="submit"
                                :disabled="form.processing || !canSubmit"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {{ form.processing ? __('Submitting...') : __('Complete Registration') }}
                                <ChevronRight class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <PaymentSelectionModal
        :is-open="showPaymentModal"
        :amount="selectedPlanAmount"
        :currency-code="systemCurrencyCode"
        :available-gateways="availablePaymentGateways"
        :default-gateway="defaultGateway"
        :submitting="form.processing"
        @close="closePaymentModal"
        @confirm="completeRegistrationWithGateway"
    />
</template>
