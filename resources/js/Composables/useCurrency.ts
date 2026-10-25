import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface CurrencyFormatOptions {
    minimumFractionDigits?: number;
    maximumFractionDigits?: number;
}

const CURRENCY_CODE_PATTERN = /^[A-Za-z]{3}$/;
const DEFAULT_CURRENCY_SYMBOL = '₹';

export default function useCurrency() {
    const page = usePage();

    const resolveCurrencySymbol = (value: string): string => {
        if (!CURRENCY_CODE_PATTERN.test(value)) {
            return value;
        }

        try {
            const formattedParts = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: value.toUpperCase(),
                currencyDisplay: 'narrowSymbol',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).formatToParts(0);

            const currencyPart = formattedParts.find((part) => part.type === 'currency')?.value?.trim();

            return currencyPart && currencyPart.length > 0 ? currencyPart : value.toUpperCase();
        } catch {
            return value.toUpperCase();
        }
    };

    const currencySymbol = computed(() => {
        const value = String((page.props.branding as any)?.business_settings?.currency_symbol ?? DEFAULT_CURRENCY_SYMBOL).trim();
        const sanitizedValue = value.length > 0 ? value : DEFAULT_CURRENCY_SYMBOL;

        return resolveCurrencySymbol(sanitizedValue);
    });

    const currencyPrefix = computed(() => `${currencySymbol.value} `);

    const formatCurrency = (value: string | number | null | undefined, options: CurrencyFormatOptions = {}): string => {
        const amount = Number(value ?? 0);
        const safeAmount = Number.isFinite(amount) ? amount : 0;
        const minimumFractionDigits = options.minimumFractionDigits ?? 2;
        const maximumFractionDigits = options.maximumFractionDigits ?? minimumFractionDigits;
        const formattedAmount = new Intl.NumberFormat('en-US', {
            minimumFractionDigits,
            maximumFractionDigits,
        }).format(safeAmount);

        return `${currencyPrefix.value}${formattedAmount}`;
    };

    return {
        currencySymbol,
        currencyPrefix,
        formatCurrency,
    };
}
