<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { CreditCard, Landmark, Wallet, X } from 'lucide-vue-next';

type PaymentGateway = 'sslcommerz' | 'stripe' | 'cash';

const props = defineProps<{
    isOpen: boolean;
    amount: number;
    currencyCode?: string;
    availableGateways?: PaymentGateway[];
    defaultGateway?: PaymentGateway;
    submitting?: boolean;
}>();

const emit = defineEmits<{
    (event: 'close'): void;
    (event: 'confirm', gateway: PaymentGateway): void;
}>();

const selectedGateway = ref<PaymentGateway>('cash');
const normalizedCurrencyCode = computed(() => {
    const value = String(props.currencyCode ?? '').trim().toUpperCase();

    return /^[A-Z]{3}$/.test(value) ? value : null;
});
const formattedAmount = computed(() => {
    const safeAmount = Number.isFinite(Number(props.amount)) ? Number(props.amount) : 0;

    if (normalizedCurrencyCode.value) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: normalizedCurrencyCode.value,
            minimumFractionDigits: 2,
        }).format(safeAmount);
    }

    return safeAmount.toFixed(2);
});
const gatewayOptions = computed<PaymentGateway[]>(() => {
    const configuredGateways = Array.isArray(props.availableGateways) ? props.availableGateways : [];
    const validGateways = configuredGateways.filter((gateway): gateway is PaymentGateway => {
        return ['sslcommerz', 'stripe', 'cash'].includes(gateway);
    });
    const uniqueGateways = validGateways.filter((gateway, index) => validGateways.indexOf(gateway) === index);

    return uniqueGateways.length > 0 ? uniqueGateways : ['cash'];
});
const gatewayLabel = (gateway: PaymentGateway): string => {
    return gateway === 'sslcommerz'
        ? 'SSLCommerz'
        : gateway === 'stripe'
          ? 'Stripe'
          : 'Cash';
};
const gatewayDescription = (gateway: PaymentGateway): string => {
    return gateway === 'sslcommerz'
        ? 'Online payments via SSLCommerz.'
        : gateway === 'stripe'
          ? 'Online card payments via Stripe.'
          : 'Pay directly at gym desk.';
};
const submitButtonText = computed(() => {
    if (props.submitting) {
        return 'Processing...';
    }

    return selectedGateway.value === 'cash' ? 'Confirm Request' : 'Continue to Payment';
});
const setDefaultGateway = (): void => {
    const fallbackGateway = gatewayOptions.value[0] ?? 'cash';
    const preferredGateway = props.defaultGateway ?? fallbackGateway;

    selectedGateway.value = gatewayOptions.value.includes(preferredGateway) ? preferredGateway : fallbackGateway;
};

watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            setDefaultGateway();
        }
    },
);

watch(
    [() => props.defaultGateway, gatewayOptions],
    () => {
        setDefaultGateway();
    },
    { immediate: true },
);

const confirmSelection = (): void => {
    emit('confirm', selectedGateway.value);
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-3 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-2 opacity-0"
        >
            <div v-if="isOpen" class="fixed inset-0 z-[170] flex items-center justify-center p-4 sm:p-6">
                <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" @click="emit('close')"></div>

                <div class="relative w-full max-w-lg overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                    <div class="flex items-start justify-between border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-6 py-5 dark:border-slate-700 dark:from-slate-900 dark:to-slate-900">
                        <div>
                            <p class="text-xs font-black tracking-widest text-primary-600 uppercase dark:text-primary-300">{{ __('Payment') }}</p>
                            <h3 class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ __('Select Payment Method') }}</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                {{ __('Choose your preferred payment method.') }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                            @click="emit('close')"
                        >
                            <X :size="18" />
                        </button>
                    </div>

                    <div class="space-y-4 p-6">
                        <button
                            v-for="gateway in gatewayOptions"
                            :key="gateway"
                            type="button"
                            class="w-full rounded-2xl border p-4 text-left transition-all"
                            :class="
                                selectedGateway === gateway
                                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-500/10'
                                    : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900'
                            "
                            @click="selectedGateway = gateway"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                        <Landmark v-if="gateway === 'sslcommerz'" :size="18" />
                                        <CreditCard v-else-if="gateway === 'stripe'" :size="18" />
                                        <Wallet v-else :size="18" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-extrabold text-slate-900 dark:text-slate-100">{{ gatewayLabel(gateway) }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __(gatewayDescription(gateway)) }}</p>
                                    </div>
                                </div>
                                <span class="text-sm font-black text-slate-700 dark:text-slate-200">{{ formattedAmount }}</span>
                            </div>
                        </button>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-200 p-6 dark:border-slate-700">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            @click="emit('close')"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button
                            type="button"
                            :disabled="submitting"
                            class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60"
                            @click="confirmSelection"
                        >
                            {{ __(submitButtonText) }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
