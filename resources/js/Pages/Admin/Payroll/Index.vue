<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Banknote, CalendarDays, Check, Clock3, Pencil, RotateCcw, UserPlus, X } from 'lucide-vue-next';
import { computed, PropType, ref, watch } from 'vue';
import dayjs from 'dayjs';
import admin from '@/routes/admin';
import useCurrency from '@/Composables/useCurrency';

interface PayrollEvent {
    id: number;
    action: 'paid' | 'reverted';
    amount: string | null;
    notes: string | null;
    occurred_at: string;
    admin?: { name: string } | null;
}

interface PayrollStaff {
    id: number;
    name: string;
    joining_date: string;
    current_salary: string;
    due_day: number;
    first_pay_period: string;
    is_active: boolean;
}

interface PayrollPeriod {
    id: number;
    period_start: string;
    due_date: string;
    salary_amount: string;
    status: 'due' | 'paid';
    actual_paid_amount: string | null;
    paid_at: string | null;
    notes: string | null;
    staff: PayrollStaff;
    events: PayrollEvent[];
}

const props = defineProps({
    periods: {
        type: Array as PropType<PayrollPeriod[]>,
        default: () => [],
    },
    staffMembers: {
        type: Array as PropType<PayrollStaff[]>,
        default: () => [],
    },
    filters: {
        type: Object as PropType<{ month: string }>,
        required: true,
    },
    summary: {
        type: Object as PropType<{
            staff_count: number;
            paid_count: number;
            due_count: number;
            due_total: number;
            paid_total: number;
        }>,
        required: true,
    },
});

const { formatCurrency } = useCurrency();
const selectedMonth = ref(props.filters.month);
const showStaffModal = ref(false);
const showPaidModal = ref(false);
const showDueModal = ref(false);
const editingStaff = ref<PayrollStaff | null>(null);
const actionPeriod = ref<PayrollPeriod | null>(null);
const staffAction = ref<PayrollStaff | null>(null);
const showDeactivateConfirm = ref(false);
const staffActionProcessing = ref(false);

const today = () => dayjs().format('YYYY-MM-DD');
const thisMonth = () => dayjs().format('YYYY-MM');

const staffForm = useForm({
    name: '',
    joining_date: today(),
    current_salary: '',
    due_day: 1,
    first_pay_period: thisMonth(),
});

const paidForm = useForm({
    amount: '',
    notes: '',
});

const dueForm = useForm({
    notes: '',
});

const periodLabel = computed(() => dayjs(`${selectedMonth.value}-01`).format('MMMM YYYY'));

watch(selectedMonth, (month) => {
    router.get(admin.payroll.index.url(), { month }, { preserveState: true, preserveScroll: true, replace: true });
});

const openCreateStaff = () => {
    editingStaff.value = null;
    staffForm.reset();
    staffForm.name = '';
    staffForm.joining_date = today();
    staffForm.current_salary = '';
    staffForm.due_day = 1;
    staffForm.first_pay_period = thisMonth();
    staffForm.clearErrors();
    showStaffModal.value = true;
};

const openEditStaff = (staff: PayrollStaff) => {
    editingStaff.value = staff;
    staffForm.name = staff.name;
    staffForm.joining_date = dayjs(staff.joining_date).format('YYYY-MM-DD');
    staffForm.current_salary = staff.current_salary;
    staffForm.due_day = staff.due_day;
    staffForm.first_pay_period = dayjs(staff.first_pay_period).format('YYYY-MM');
    staffForm.clearErrors();
    showStaffModal.value = true;
};

const closeStaffModal = () => {
    showStaffModal.value = false;
    editingStaff.value = null;
    staffForm.clearErrors();
};

const submitStaff = () => {
    const options = {
        preserveScroll: true,
        onSuccess: closeStaffModal,
    };

    if (editingStaff.value) {
        staffForm.put(admin.payroll.staff.update.url(editingStaff.value.id), options);

        return;
    }

    staffForm.post(admin.payroll.staff.store.url(), options);
};

const openMarkPaid = (period: PayrollPeriod) => {
    actionPeriod.value = period;
    paidForm.amount = period.salary_amount;
    paidForm.notes = '';
    paidForm.clearErrors();
    showPaidModal.value = true;
};

const submitMarkPaid = () => {
    if (!actionPeriod.value) {
        return;
    }

    paidForm.patch(admin.payroll.periods.paid.url(actionPeriod.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPaidModal.value = false;
            actionPeriod.value = null;
        },
    });
};

const openMarkDue = (period: PayrollPeriod) => {
    actionPeriod.value = period;
    dueForm.notes = '';
    dueForm.clearErrors();
    showDueModal.value = true;
};

const submitMarkDue = () => {
    if (!actionPeriod.value) {
        return;
    }

    dueForm.patch(admin.payroll.periods.due.url(actionPeriod.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDueModal.value = false;
            actionPeriod.value = null;
        },
    });
};

const confirmStaffStatus = (staff: PayrollStaff) => {
    staffAction.value = staff;
    showDeactivateConfirm.value = true;
};

const updateStaffStatus = () => {
    if (!staffAction.value) {
        return;
    }

    staffActionProcessing.value = true;
    router.patch(
        admin.payroll.staff.status.url(staffAction.value.id),
        { is_active: !staffAction.value.is_active },
        {
            preserveScroll: true,
            onSuccess: closeStaffModal,
            onFinish: () => {
                staffActionProcessing.value = false;
                showDeactivateConfirm.value = false;
                staffAction.value = null;
            },
        },
    );
};

const formatDate = (value: string | null): string => (value ? dayjs(value).format('MMM D, YYYY') : '—');
</script>

<template>
    <Head :title="__('Staff Payroll')" />

    <AdminLayout>
        <div class="space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white">{{ __('Staff Payroll') }}</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Track monthly trainer and staff salaries, payment status, and adjustments.') }}
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 text-sm font-bold text-white shadow-sm transition-colors hover:bg-brand-700"
                    @click="openCreateStaff"
                >
                    <UserPlus :size="17" /> {{ __('Add Staff Member') }}
                </button>
            </header>

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
                    <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                        <span class="text-xs font-bold uppercase">{{ __('Staff this period') }}</span>
                        <Banknote :size="18" />
                    </div>
                    <p class="mt-3 text-2xl font-black text-slate-900 dark:text-white">{{ summary.staff_count }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/30 dark:bg-amber-500/10">
                    <div class="flex items-center justify-between text-amber-700 dark:text-amber-300">
                        <span class="text-xs font-bold uppercase">{{ __('Due') }}</span>
                        <Clock3 :size="18" />
                    </div>
                    <p class="mt-3 text-xl font-black text-amber-800 dark:text-amber-200">{{ summary.due_count }}</p>
                    <p class="mt-1 text-sm font-semibold text-amber-700 dark:text-amber-300">{{ formatCurrency(summary.due_total) }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                    <div class="flex items-center justify-between text-emerald-700 dark:text-emerald-300">
                        <span class="text-xs font-bold uppercase">{{ __('Paid') }}</span>
                        <Check :size="18" />
                    </div>
                    <p class="mt-3 text-xl font-black text-emerald-800 dark:text-emerald-200">{{ summary.paid_count }}</p>
                    <p class="mt-1 text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ formatCurrency(summary.paid_total) }}</p>
                </div>
                <label class="flex flex-col justify-center rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
                    <span class="mb-2 text-xs font-bold uppercase text-slate-500 dark:text-slate-400">{{ __('Payroll month') }}</span>
                    <span class="relative">
                        <CalendarDays :size="17" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="selectedMonth"
                            type="month"
                            class="h-11 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3 text-sm font-semibold text-slate-800 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                        />
                    </span>
                </label>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-col gap-1 border-b border-slate-200 px-5 py-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-extrabold text-slate-900 dark:text-white">{{ __('Salary payments') }}</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ periodLabel }}</p>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ __('New salary periods are generated monthly. Salary changes apply to future periods.') }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[950px] text-left">
                        <thead class="bg-slate-50 text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:bg-slate-800/60 dark:text-slate-400">
                            <tr>
                                <th class="px-5 py-3">{{ __('Staff member') }}</th>
                                <th class="px-5 py-3">{{ __('Salary') }}</th>
                                <th class="px-5 py-3">{{ __('Due date') }}</th>
                                <th class="px-5 py-3">{{ __('Payment status') }}</th>
                                <th class="px-5 py-3">{{ __('Actual paid') }}</th>
                                <th class="px-5 py-3">{{ __('Notes / history') }}</th>
                                <th class="px-5 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="period in periods" :key="period.id" class="align-top">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ period.staff.name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Joined') }} {{ formatDate(period.staff.joining_date) }}</p>
                                    <span
                                        v-if="!period.staff.is_active"
                                        class="mt-2 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 dark:bg-slate-800"
                                    >
                                        {{ __('Inactive') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm font-semibold text-slate-800 dark:text-slate-200">
                                    {{ formatCurrency(period.salary_amount) }}
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600 dark:text-slate-300">{{ formatDate(period.due_date) }}</td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold"
                                        :class="
                                            period.status === 'paid'
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                                                : 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300'
                                        "
                                    >
                                        {{ period.status === 'paid' ? __('Paid') : __('Due') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm font-semibold text-slate-800 dark:text-slate-200">
                                    {{ period.actual_paid_amount === null ? '—' : formatCurrency(period.actual_paid_amount) }}
                                    <p v-if="period.paid_at" class="mt-1 text-xs font-normal text-slate-500">{{ formatDate(period.paid_at) }}</p>
                                </td>
                                <td class="max-w-xs px-5 py-4 text-sm text-slate-600 dark:text-slate-300">
                                    <p v-if="period.notes" class="line-clamp-2">{{ period.notes }}</p>
                                    <p v-else class="text-slate-400">—</p>
                                    <details v-if="period.events.length" class="mt-2">
                                        <summary class="cursor-pointer text-xs font-semibold text-brand-600 dark:text-brand-300">
                                            {{ __('Payment history') }} ({{ period.events.length }})
                                        </summary>
                                        <div class="mt-2 space-y-2">
                                            <div v-for="event in period.events" :key="event.id" class="rounded-lg bg-slate-50 p-2 text-xs dark:bg-slate-800">
                                                <p class="font-semibold text-slate-700 dark:text-slate-200">
                                                    {{ event.action === 'paid' ? __('Marked paid') : __('Reverted to due') }}
                                                    · {{ formatDate(event.occurred_at) }}
                                                </p>
                                                <p v-if="event.amount !== null" class="mt-0.5">{{ formatCurrency(event.amount) }}</p>
                                                <p v-if="event.notes" class="mt-0.5 whitespace-pre-wrap">{{ event.notes }}</p>
                                                <p v-if="event.admin" class="mt-0.5 text-slate-400">{{ event.admin.name }}</p>
                                            </div>
                                        </div>
                                    </details>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-300 px-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                            @click="openEditStaff(period.staff)"
                                        >
                                            <Pencil :size="14" /> {{ __('Edit') }}
                                        </button>
                                        <button
                                            v-if="period.status === 'due'"
                                            type="button"
                                            class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-emerald-600 px-2.5 text-xs font-bold text-white hover:bg-emerald-700"
                                            @click="openMarkPaid(period)"
                                        >
                                            <Check :size="14" /> {{ __('Mark Paid') }}
                                        </button>
                                        <button
                                            v-else
                                            type="button"
                                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-amber-300 px-2.5 text-xs font-bold text-amber-800 hover:bg-amber-50 dark:border-amber-500/30 dark:text-amber-300 dark:hover:bg-amber-500/10"
                                            @click="openMarkDue(period)"
                                        >
                                            <RotateCcw :size="14" /> {{ __('Revert to Due') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="periods.length === 0">
                                <td colspan="7" class="px-5 py-14 text-center">
                                    <Banknote :size="28" class="mx-auto text-slate-300 dark:text-slate-600" />
                                    <p class="mt-3 font-semibold text-slate-700 dark:text-slate-200">{{ __('No salary periods for this month yet.') }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ __('Add a staff member or choose a month within their payroll period.') }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="text-lg font-extrabold text-slate-900 dark:text-white">{{ __('Payroll staff directory') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Manage staff records, current salaries, and payroll activity.') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] text-left">
                        <thead class="bg-slate-50 text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:bg-slate-800/60 dark:text-slate-400">
                            <tr>
                                <th class="px-5 py-3">{{ __('Staff member') }}</th>
                                <th class="px-5 py-3">{{ __('Joining date') }}</th>
                                <th class="px-5 py-3">{{ __('Current salary') }}</th>
                                <th class="px-5 py-3">{{ __('Due day') }}</th>
                                <th class="px-5 py-3">{{ __('First period') }}</th>
                                <th class="px-5 py-3">{{ __('Status') }}</th>
                                <th class="px-5 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="staff in staffMembers" :key="staff.id">
                                <td class="px-5 py-3.5 font-bold text-slate-900 dark:text-white">{{ staff.name }}</td>
                                <td class="px-5 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ formatDate(staff.joining_date) }}</td>
                                <td class="px-5 py-3.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ formatCurrency(staff.current_salary) }}</td>
                                <td class="px-5 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ staff.due_day }}</td>
                                <td class="px-5 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ dayjs(staff.first_pay_period).format('MMM YYYY') }}</td>
                                <td class="px-5 py-3.5">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-bold"
                                        :class="staff.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                                    >
                                        {{ staff.is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            class="rounded-lg border border-slate-300 px-2.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                            @click="openEditStaff(staff)"
                                        >
                                            {{ __('Edit') }}
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-lg border border-slate-300 px-2.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                            @click="confirmStaffStatus(staff)"
                                        >
                                            {{ staff.is_active ? __('Deactivate') : __('Reactivate') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="staffMembers.length === 0">
                                <td colspan="7" class="px-5 py-8 text-center text-sm text-slate-500">{{ __('No payroll staff added yet.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div v-if="showStaffModal" class="fixed inset-0 z-[170] flex items-center justify-center overflow-y-auto bg-slate-950/55 p-4 backdrop-blur-sm">
            <form @submit.prevent="submitStaff" class="w-full max-w-xl space-y-5 rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900">
                <header class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">
                            {{ editingStaff ? __('Edit Payroll Staff') : __('Add Staff Member') }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Salary changes apply to future periods; recorded periods remain unchanged.') }}</p>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" @click="closeStaffModal">
                        <X :size="18" />
                    </button>
                </header>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase text-slate-500">{{ __('Name') }}</label>
                    <input v-model="staffForm.name" required maxlength="255" type="text" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm dark:border-slate-700 dark:bg-slate-950" />
                    <p v-if="staffForm.errors.name" class="mt-1 text-xs text-red-600">{{ staffForm.errors.name }}</p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase text-slate-500">{{ __('Joining date') }}</label>
                        <input v-model="staffForm.joining_date" required type="date" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm dark:border-slate-700 dark:bg-slate-950" />
                        <p v-if="staffForm.errors.joining_date" class="mt-1 text-xs text-red-600">{{ staffForm.errors.joining_date }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase text-slate-500">{{ __('Current monthly salary') }}</label>
                        <input v-model="staffForm.current_salary" required min="0.01" step="0.01" type="number" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm dark:border-slate-700 dark:bg-slate-950" />
                        <p v-if="staffForm.errors.current_salary" class="mt-1 text-xs text-red-600">{{ staffForm.errors.current_salary }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase text-slate-500">{{ __('Monthly payment due day') }}</label>
                        <input v-model.number="staffForm.due_day" required min="1" max="31" type="number" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm dark:border-slate-700 dark:bg-slate-950" />
                        <p class="mt-1 text-xs text-slate-500">{{ __('For short months, the due date is the last day of that month.') }}</p>
                        <p v-if="staffForm.errors.due_day" class="mt-1 text-xs text-red-600">{{ staffForm.errors.due_day }}</p>
                    </div>
                    <div v-if="!editingStaff">
                        <label class="mb-1.5 block text-xs font-bold uppercase text-slate-500">{{ __('First payroll period') }}</label>
                        <input v-model="staffForm.first_pay_period" required type="month" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm dark:border-slate-700 dark:bg-slate-950" />
                        <p v-if="staffForm.errors.first_pay_period" class="mt-1 text-xs text-red-600">{{ staffForm.errors.first_pay_period }}</p>
                    </div>
                </div>

                <div v-if="editingStaff" class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 dark:border-slate-700">
                    <div>
                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ editingStaff.is_active ? __('Active') : __('Inactive') }}</p>
                        <p class="text-xs text-slate-500">{{ __('Deactivation stops future payroll periods and preserves history.') }}</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold dark:border-slate-700"
                        @click="confirmStaffStatus(editingStaff)"
                    >
                        {{ editingStaff.is_active ? __('Deactivate') : __('Reactivate') }}
                    </button>
                </div>

                <footer class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-700">
                    <button type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300" @click="closeStaffModal">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" :disabled="staffForm.processing" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-60">
                        {{ staffForm.processing ? __('Saving...') : __('Save Staff') }}
                    </button>
                </footer>
            </form>
        </div>

        <div v-if="showPaidModal && actionPeriod" class="fixed inset-0 z-[180] flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
            <form @submit.prevent="submitMarkPaid" class="w-full max-w-lg space-y-4 rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900">
                <header class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ __('Record Salary Payment') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ actionPeriod.staff.name }} · {{ periodLabel }}</p>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" @click="showPaidModal = false">
                        <X :size="18" />
                    </button>
                </header>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase text-slate-500">{{ __('Actual amount paid') }}</label>
                    <input v-model="paidForm.amount" required min="0" step="0.01" type="number" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm dark:border-slate-700 dark:bg-slate-950" />
                    <p class="mt-1 text-xs text-slate-500">{{ __('Defaults to the monthly salary. Adjust for bonus or deductions.') }}</p>
                    <p v-if="paidForm.errors.amount" class="mt-1 text-xs text-red-600">{{ paidForm.errors.amount }}</p>
                    <p v-if="paidForm.errors.status" class="mt-1 text-xs text-red-600">{{ paidForm.errors.status }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase text-slate-500">{{ __('Optional notes') }}</label>
                    <textarea v-model="paidForm.notes" rows="3" maxlength="5000" :placeholder="__('Bonus, deduction, or other payment details')" class="w-full rounded-xl border border-slate-300 bg-white p-3 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                    <p v-if="paidForm.errors.notes" class="mt-1 text-xs text-red-600">{{ paidForm.errors.notes }}</p>
                </div>
                <footer class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-700">
                    <button type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold dark:border-slate-700" @click="showPaidModal = false">{{ __('Cancel') }}</button>
                    <button type="submit" :disabled="paidForm.processing" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-60">{{ __('Confirm Paid') }}</button>
                </footer>
            </form>
        </div>

        <div v-if="showDueModal && actionPeriod" class="fixed inset-0 z-[180] flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
            <form @submit.prevent="submitMarkDue" class="w-full max-w-lg space-y-4 rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900">
                <header class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ __('Revert Payment to Due') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ actionPeriod.staff.name }} · {{ periodLabel }}</p>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" @click="showDueModal = false">
                        <X :size="18" />
                    </button>
                </header>
                <p class="rounded-xl bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
                    {{ __('This records a correction and keeps the original payment in the period history.') }}
                </p>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase text-slate-500">{{ __('Optional correction note') }}</label>
                    <textarea v-model="dueForm.notes" rows="3" maxlength="5000" class="w-full rounded-xl border border-slate-300 bg-white p-3 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                    <p v-if="dueForm.errors.notes" class="mt-1 text-xs text-red-600">{{ dueForm.errors.notes }}</p>
                    <p v-if="dueForm.errors.status" class="mt-1 text-xs text-red-600">{{ dueForm.errors.status }}</p>
                </div>
                <footer class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-700">
                    <button type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold dark:border-slate-700" @click="showDueModal = false">{{ __('Cancel') }}</button>
                    <button type="submit" :disabled="dueForm.processing" class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-60">{{ __('Record Correction') }}</button>
                </footer>
            </form>
        </div>

        <div v-if="showDeactivateConfirm && staffAction" class="fixed inset-0 z-[190] flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md space-y-4 rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900">
                <h2 class="text-lg font-extrabold text-slate-900 dark:text-white">
                    {{ staffAction.is_active ? __('Deactivate staff member?') : __('Reactivate staff member?') }}
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    {{
                        staffAction.is_active
                            ? __('No future salary periods will be generated. Existing records will remain available.')
                            : __('Future monthly salary periods will be generated again.')
                    }}
                </p>
                <footer class="flex justify-end gap-2">
                    <button type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold dark:border-slate-700" @click="showDeactivateConfirm = false">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" :disabled="staffActionProcessing" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-60" @click="updateStaffStatus">
                        {{ staffAction.is_active ? __('Deactivate') : __('Reactivate') }}
                    </button>
                </footer>
            </div>
        </div>
    </AdminLayout>
</template>
