<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, type CSSProperties } from 'vue';

type TooltipSide = 'top' | 'right' | 'bottom' | 'left';

const props = withDefaults(defineProps<{
    text?: string;
    side?: TooltipSide | 'auto';
    delayDuration?: number;
}>(), {
    text: '',
    side: 'auto',
    delayDuration: 0,
});

const triggerRef = ref<HTMLElement | null>(null);
const tooltipRef = ref<HTMLElement | null>(null);
const isOpen = ref(false);
const resolvedSide = ref<TooltipSide>('top');
const tooltipStyle = ref<CSSProperties>({
    top: '0px',
    left: '0px',
});

const edgePadding = 8;
const sideOffset = 10;
let openTimer: ReturnType<typeof setTimeout> | null = null;

const clearOpenTimer = (): void => {
    if (openTimer) {
        clearTimeout(openTimer);
        openTimer = null;
    }
};

const clamp = (value: number, min: number, max: number): number => {
    return Math.min(Math.max(value, min), max);
};

const chooseSide = (triggerRect: DOMRect, tooltipWidth: number, tooltipHeight: number): TooltipSide => {
    if (props.side !== 'auto') {
        return props.side;
    }

    const available = {
        top: triggerRect.top - edgePadding,
        bottom: window.innerHeight - triggerRect.bottom - edgePadding,
        left: triggerRect.left - edgePadding,
        right: window.innerWidth - triggerRect.right - edgePadding,
    };

    const canFit = {
        top: available.top >= tooltipHeight + sideOffset,
        bottom: available.bottom >= tooltipHeight + sideOffset,
        left: available.left >= tooltipWidth + sideOffset,
        right: available.right >= tooltipWidth + sideOffset,
    };

    const preferredOrder: TooltipSide[] = ['top', 'bottom', 'right', 'left'];

    for (const candidate of preferredOrder) {
        if (canFit[candidate]) {
            return candidate;
        }
    }

    return preferredOrder.reduce((best, candidate) => {
        return available[candidate] > available[best] ? candidate : best;
    }, 'top' as TooltipSide);
};

const updatePosition = (): void => {
    if (!isOpen.value || !triggerRef.value || !tooltipRef.value) {
        return;
    }

    const triggerRect = triggerRef.value.getBoundingClientRect();
    const tooltipRect = tooltipRef.value.getBoundingClientRect();
    const side = chooseSide(triggerRect, tooltipRect.width, tooltipRect.height);

    resolvedSide.value = side;

    let top = 0;
    let left = 0;

    if (side === 'top') {
        top = triggerRect.top - tooltipRect.height - sideOffset;
        left = triggerRect.left + triggerRect.width / 2 - tooltipRect.width / 2;
    }

    if (side === 'bottom') {
        top = triggerRect.bottom + sideOffset;
        left = triggerRect.left + triggerRect.width / 2 - tooltipRect.width / 2;
    }

    if (side === 'left') {
        top = triggerRect.top + triggerRect.height / 2 - tooltipRect.height / 2;
        left = triggerRect.left - tooltipRect.width - sideOffset;
    }

    if (side === 'right') {
        top = triggerRect.top + triggerRect.height / 2 - tooltipRect.height / 2;
        left = triggerRect.right + sideOffset;
    }

    const maxLeft = window.innerWidth - tooltipRect.width - edgePadding;
    const maxTop = window.innerHeight - tooltipRect.height - edgePadding;

    tooltipStyle.value = {
        top: `${clamp(top, edgePadding, Math.max(edgePadding, maxTop))}px`,
        left: `${clamp(left, edgePadding, Math.max(edgePadding, maxLeft))}px`,
    };
};

const addViewportListeners = (): void => {
    window.addEventListener('resize', updatePosition);
    window.addEventListener('scroll', updatePosition, true);
};

const removeViewportListeners = (): void => {
    window.removeEventListener('resize', updatePosition);
    window.removeEventListener('scroll', updatePosition, true);
};

const open = async (): Promise<void> => {
    if (!props.text) {
        return;
    }

    clearOpenTimer();
    openTimer = setTimeout(async () => {
        isOpen.value = true;
        await nextTick();
        updatePosition();
        addViewportListeners();
    }, props.delayDuration);
};

const close = (): void => {
    clearOpenTimer();
    isOpen.value = false;
    removeViewportListeners();
};

const arrowClass = computed(() => {
    return {
        top: 'top-full left-1/2 -ml-1 border-t-black/90',
        right: 'top-1/2 right-full -mt-1 border-r-black/90',
        bottom: 'bottom-full left-1/2 -ml-1 border-b-black/90',
        left: 'top-1/2 left-full -mt-1 border-l-black/90',
    }[resolvedSide.value];
});

onBeforeUnmount(() => {
    close();
});
</script>

<template>
    <span
        ref="triggerRef"
        class="inline-flex"
        @pointerenter="open"
        @pointerleave="close"
        @mouseenter="open"
        @mouseleave="close"
        @focusin="open"
        @focusout="close"
    >
        <slot />
    </span>

    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="translate-y-1 scale-95 opacity-0"
            enter-to-class="translate-y-0 scale-100 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="translate-y-0 scale-100 opacity-100"
            leave-to-class="translate-y-1 scale-95 opacity-0"
        >
            <div
                v-if="isOpen && text"
                ref="tooltipRef"
                role="tooltip"
                class="pointer-events-none fixed z-[120]"
                :style="tooltipStyle"
            >
                <div class="relative w-max max-w-[calc(100vw-1rem)] rounded-md border border-orange-500/30 bg-black/90 px-3 py-1.5 text-xs font-semibold text-white shadow-xl">
                    <p class="whitespace-normal break-words">{{ text }}</p>
                    <div class="absolute h-0 w-0 border-4 border-transparent" :class="arrowClass"></div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
