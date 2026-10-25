<script setup lang="ts">
import { destroy, toggleActive } from '@/actions/App/Http/Controllers/Admin/PlanAccessTypeController';
import AppTooltip from '@/Components/Common/AppTooltip.vue';
import ConfirmationModal from '@/Components/Common/ConfirmationModal.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import admin from '@/routes/admin';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, PropType, ref, watch } from 'vue';
import { PlusCircle, Clock, Trash2, Edit3, Power, AlertCircle, ShieldCheck, Search, ArrowLeft } from 'lucide-vue-next';
import AccessTypeFormModal from './Partials/AccessTypeFormModal.vue';

const props = defineProps({
    accessTypes: Object as PropType<any>,
    totalAccessTypes: Number as PropType<number>,
    activeAccessTypesCount: Number as PropType<number>,
    filters: Object as PropType<{ search?: string }>,
});

const search = ref(props.filters?.search ?? '');

const accessTypeList = computed(() => props.accessTypes?.data ?? []);
const hasAccessTypes = computed(() => accessTypeList.value.length > 0);
const paginationLinks = computed(() => props.accessTypes?.links ?? []);
const activeAccessTypeCount = computed(() => Number(props.activeAccessTypesCount ?? 0));
const filteredCount = computed(() => Number(props.accessTypes?.total ?? 0));

watch(
    search,
    debounce((value: string) => {
        router.get(admin.plans.accessTypes.index.url(), { search: value || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    }, 300),
);

const showDeleteModal = ref(false);
const showFormModal = ref(false);
const showStatusModal = ref(false);
const accessTypeToDelete = ref<any>(null);
const accessTypeToToggle = ref<any>(null);
const editingAccessType = ref<any>(null);

const openCreateModal = () => {
    editingAccessType.value = null;
    showFormModal.value = true;
};

const openEditModal = (type: any) => {
    editingAccessType.value = type;
    showFormModal.value = true;
};

const canDeleteAccessType = (type: any): boolean => {
    return getDeleteDisabledReason(type) === null;
};

const getDeleteDisabledReason = (type: any): string | null => {
    if (Number(props.totalAccessTypes ?? 0) <= 1) {
        return 'At least one access type must remain.';
    }

    if (Number(type.plans_count ?? 0) > 0) {
        return 'Cannot delete while assigned to one or more plans.';
    }

    return null;
};

const confirmDelete = (type: any) => {
    if (!canDeleteAccessType(type)) {
        return;
    }

    accessTypeToDelete.value = type;
    showDeleteModal.value = true;
};

const canToggleActiveStatus = (type: any): boolean => {
    return !(type.is_active && activeAccessTypeCount.value <= 1);
};

const requestStatusToggle = (type: any) => {
    if (!canToggleActiveStatus(type)) {
        return;
    }

    accessTypeToToggle.value = type;
    showStatusModal.value = true;
};

const handleStatusToggle = () => {
    if (!accessTypeToToggle.value) {
        return;
    }

    router.patch(
        toggleActive.url(accessTypeToToggle.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showStatusModal.value = false;
                accessTypeToToggle.value = null;
            },
        },
    );
};

const handleDelete = () => {
    if (!accessTypeToDelete.value) {
        return;
    }

    router.delete(destroy.url(accessTypeToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            accessTypeToDelete.value = null;
        },
    });
};

const formatTime = (time: string | null) => {
    if (!time) {
        return '--';
    }

    const [hourString = '0', minuteString = '00'] = time.split(':');
    const hour = Number(hourString);
    const normalizedHour = Number.isNaN(hour) ? 0 : hour;
    const period = normalizedHour >= 12 ? 'PM' : 'AM';
    const hour12 = normalizedHour % 12 || 12;

    return `${hour12}:${minuteString} ${period}`;
};
</script>

<template>
    <Head :title="__('Plan Access Types')" />

    <AdminLayout>
        <div class="animate-fade-in space-y-8">
            <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ __('Plan Access Types') }}</h2>
                    <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">
                        {{ __('Define access schedules and availability rules for membership plans.') }}
                    </p>
                    <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                        {{ __('Total') }}: {{ totalAccessTypes || 0 }} | {{ __('Showing') }}: {{ filteredCount }}
                    </p>
                </div>
                <div class="flex w-full flex-col items-stretch gap-3 sm:w-auto sm:flex-row sm:items-center">
                    <Link
                        :href="admin.plans.index.url()"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 text-sm font-bold text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        <ArrowLeft :size="16" />
                        {{ __('Back to Plans') }}
                    </Link>

                    <button
                        @click.prevent="openCreateModal"
                        class="group flex h-11 cursor-pointer items-center justify-center rounded-2xl bg-primary-600 px-6 text-sm font-bold text-white shadow-xl shadow-primary-500/20 transition-all hover:bg-primary-700 active:scale-95"
                    >
                        <PlusCircle :size="18" class="me-2 transition-transform group-hover:rotate-90" />
                        {{ __('Create Plan Access Type') }}
                    </button>
                </div>
            </div>

            <div class="overflow-hidden rounded-[2rem] border border-slate-200/70 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 p-4 dark:border-slate-800">
                    <div class="relative w-full max-w-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <Search :size="16" class="text-slate-400" />
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="__('Search access types...')"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pr-4 pl-10 text-sm text-slate-700 placeholder:text-slate-400 focus:ring-2 focus:ring-primary-500/20 focus:outline-none dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 dark:placeholder:text-slate-500"
                        />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[880px]">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/80 dark:border-slate-800 dark:bg-slate-800/50">
                                <th class="px-6 py-4 text-left text-[11px] font-black tracking-widest text-slate-500 uppercase">{{ __('Name') }}</th>
                                <th class="px-6 py-4 text-left text-[11px] font-black tracking-widest text-slate-500 uppercase">{{ __('Slug') }}</th>
                                <th class="px-6 py-4 text-left text-[11px] font-black tracking-widest text-slate-500 uppercase">
                                    {{ __('Schedule') }}
                                </th>
                                <th class="px-6 py-4 text-left text-[11px] font-black tracking-widest text-slate-500 uppercase">{{ __('Plans') }}</th>
                                <th class="px-6 py-4 text-left text-[11px] font-black tracking-widest text-slate-500 uppercase">
                                    {{ __('Status') }}
                                </th>
                                <th class="px-6 py-4 text-right text-[11px] font-black tracking-widest text-slate-500 uppercase">
                                    {{ __('Action') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!hasAccessTypes">
                                <td colspan="6" class="px-6 py-16 text-center text-sm font-medium text-slate-500 dark:text-slate-400">
                                    {{ __('No access types found.') }}
                                </td>
                            </tr>
                            <tr
                                v-for="type in accessTypeList"
                                :key="type.id"
                                class="border-b border-slate-100 transition-colors last:border-b-0 hover:bg-slate-50/70 dark:border-slate-800 dark:hover:bg-slate-800/30"
                            >
                                <td class="px-6 py-4 align-top">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ type.name }}</p>
                                    <p class="mt-1 max-w-sm text-xs text-slate-500 dark:text-slate-400">
                                        {{ type.description || __('No custom parameters defined for this plan access type.') }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <span
                                        class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        {{ type.slug }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <div
                                        v-if="type.is_anytime"
                                        class="flex items-center gap-2 text-xs font-bold text-primary-600 dark:text-primary-400"
                                    >
                                        <ShieldCheck :size="14" />
                                        <span>{{ __('Anytime Access') }}</span>
                                    </div>
                                    <div v-else class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300">
                                        <Clock :size="14" />
                                        <span>{{ formatTime(type.start_time) }} - {{ formatTime(type.end_time) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <span
                                        class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        {{ type.plans_count || 0 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <AppTooltip :text="!canToggleActiveStatus(type) ? __('At least one active access type is required.') : ''">
                                        <button
                                            @click="requestStatusToggle(type)"
                                            class="flex h-9 w-9 items-center justify-center rounded-xl transition-colors"
                                            :class="[
                                                canToggleActiveStatus(type) ? 'cursor-pointer' : 'cursor-not-allowed opacity-40',
                                                type.is_active
                                                    ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10'
                                                    : 'bg-slate-100 text-slate-400 dark:bg-slate-800',
                                            ]"
                                        >
                                            <Power :size="18" class="pointer-events-none" />
                                        </button>
                                    </AppTooltip>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <div class="flex items-center justify-end gap-2">
                                        <AppTooltip :text="getDeleteDisabledReason(type) ? __(getDeleteDisabledReason(type) as string) : ''">
                                            <button
                                                @click="confirmDelete(type)"
                                                :disabled="!canDeleteAccessType(type)"
                                                class="flex h-9 w-9 items-center justify-center rounded-xl transition-colors disabled:cursor-not-allowed disabled:opacity-40"
                                                :class="
                                                    canDeleteAccessType(type)
                                                        ? 'cursor-pointer text-slate-400 hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-500/10'
                                                        : 'text-slate-300 dark:text-slate-600'
                                                "
                                            >
                                                <Trash2 :size="18" class="pointer-events-none" />
                                            </button>
                                        </AppTooltip>

                                        <button
                                            @click="openEditModal(type)"
                                            class="inline-flex h-9 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                                        >
                                            <Edit3 :size="14" /> {{ __('Edit') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="hasAccessTypes && paginationLinks.length > 3"
                    class="flex items-center justify-between border-t border-slate-200 px-6 py-4 dark:border-slate-800"
                >
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ __('Page') }} {{ accessTypes.current_page }} / {{ accessTypes.last_page }}
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, index) in paginationLinks"
                            :key="index"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="rounded-lg px-3 py-1 text-xs font-semibold transition-colors"
                            :class="
                                link.active
                                    ? 'bg-primary-600 text-white'
                                    : 'text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'
                            "
                            :preserve-state="true"
                            :preserve-scroll="true"
                        />
                    </div>
                </div>
            </div>

            <div
                class="flex items-start gap-4 rounded-[2rem] border border-primary-100 bg-primary-50 p-6 dark:border-primary-500/20 dark:bg-primary-500/5"
            >
                <div class="rounded-xl bg-primary-100 p-2 text-primary-600 dark:bg-primary-500/20 dark:text-primary-500">
                    <AlertCircle :size="20" />
                </div>
                <div>
                    <h4 class="text-sm font-black tracking-tight text-primary-900 uppercase dark:text-primary-200">
                        {{ __('Dynamic Access Logic') }}
                    </h4>
                    <p class="mt-1 text-xs leading-relaxed font-medium text-primary-700 transition-colors dark:text-primary-400">
                        Access types defined here will automatically appear as options when creating or editing Membership Plans. Deleting an access
                        type is only permitted if it is not currently assigned to any membership plans, and at least one access type remains in the
                        system.
                    </p>
                </div>
            </div>
        </div>

        <AccessTypeFormModal :show="showFormModal" :access-type="editingAccessType" @close="showFormModal = false" />

        <ConfirmationModal
            :show="showDeleteModal"
            :title="__('Delete Plan Access Type?')"
            :message="__('Are you sure you want to delete this plan access type? It will no longer be available for plan assignments.')"
            :confirmText="__('Yes, Delete Access Type')"
            :cancelText="__('No, Keep Access Type')"
            type="danger"
            @close="showDeleteModal = false"
            @confirm="handleDelete"
        />

        <ConfirmationModal
            :show="showStatusModal"
            :title="accessTypeToToggle?.is_active ? __('Deactivate Access Type?') : __('Activate Access Type?')"
            :message="
                accessTypeToToggle?.is_active
                    ? __('This access type will no longer be available when creating or editing plans.')
                    : __('This access type will become available when creating or editing plans.')
            "
            :confirmText="accessTypeToToggle?.is_active ? __('Yes, Deactivate') : __('Yes, Activate')"
            :cancelText="__('Cancel')"
            type="warning"
            @close="
                showStatusModal = false;
                accessTypeToToggle = null;
            "
            @confirm="handleStatusToggle"
        />
    </AdminLayout>
</template>
