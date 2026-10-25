<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { PlusCircle, Edit3, Trash2, ArrowLeft, AlertCircle } from 'lucide-vue-next';
import type { PropType} from 'vue';
import { computed, ref, watch } from 'vue';
import { destroy } from '@/actions/App/Http/Controllers/Admin/PlanFacilityController';
import AppTooltip from '@/Components/Common/AppTooltip.vue';
import ConfirmationModal from '@/Components/Common/ConfirmationModal.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import admin from '@/routes/admin';
import FacilityFormModal from './Partials/FacilityFormModal.vue';

const props = defineProps({
    facilities: Object as PropType<any>,
    totalFacilities: Number as PropType<number>,
    filters: Object as PropType<{ search?: string }>,
});

const search = ref(props.filters?.search ?? '');
const facilityList = computed(() => props.facilities?.data ?? []);
const hasFacilities = computed(() => facilityList.value.length > 0);
const paginationLinks = computed(() => props.facilities?.links ?? []);
const filteredFacilitiesCount = computed(() => Number(props.facilities?.total ?? 0));

watch(
    search,
    debounce((value: string) => {
        router.get(
            admin.plans.facilities.index.url(),
            { search: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300),
);

const showFormModal = ref(false);
const showDeleteModal = ref(false);
const facilityToDelete = ref<any>(null);
const editingFacility = ref<any>(null);

const openCreateModal = (): void => {
    editingFacility.value = null;
    showFormModal.value = true;
};

const openEditModal = (facility: any): void => {
    if (getEditDisabledReason(facility)) {
        return;
    }

    editingFacility.value = facility;
    showFormModal.value = true;
};

const getEditDisabledReason = (facility: any): string => {
    if (facility.is_default) {
        return 'Default facility cannot be edited.';
    }

    return '';
};

const getDeleteDisabledReason = (facility: any): string => {
    if (facility.is_default) {
        return 'Default facility cannot be deleted.';
    }

    if (Number(facility.plans_count ?? 0) > 0) {
        return 'Cannot delete while assigned to one or more plans.';
    }

    return '';
};

const confirmDelete = (facility: any): void => {
    if (getDeleteDisabledReason(facility)) {
        return;
    }

    facilityToDelete.value = facility;
    showDeleteModal.value = true;
};

const handleDelete = (): void => {
    if (!facilityToDelete.value) {
        return;
    }

    router.delete(destroy.url(facilityToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            facilityToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head :title="__('Facilities')" />

    <AdminLayout>
        <div class="animate-fade-in space-y-8">
            <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ __('Facilities') }}</h2>
                    <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">
                        {{ __('Create and manage facilities used in plan create/edit modal.') }}
                    </p>
                    <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                        {{ __('Total') }}: {{ totalFacilities || 0 }} | {{ __('Showing') }}: {{ filteredFacilitiesCount }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="admin.plans.index.url()"
                        class="inline-flex h-11 items-center rounded-2xl border border-slate-200 bg-white px-5 text-sm font-bold text-slate-700 shadow-sm transition-all hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        <ArrowLeft :size="16" class="me-2" />
                        {{ __('Back to Plans') }}
                    </Link>
                    <button
                        @click.prevent="openCreateModal"
                        class="group inline-flex h-11 items-center rounded-2xl bg-primary-600 px-6 text-sm font-bold text-white shadow-xl shadow-primary-500/20 transition-all hover:bg-primary-700 active:scale-95"
                    >
                        <PlusCircle :size="18" class="me-2 transition-transform group-hover:rotate-90" />
                        {{ __('Create Facility') }}
                    </button>
                </div>
            </div>

            <div class="overflow-hidden rounded-[2rem] border border-slate-200/70 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 p-4 dark:border-slate-800">
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="__('Search facilities...')"
                        class="h-11 w-full max-w-sm rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-700 placeholder:text-slate-400 focus:ring-2 focus:ring-primary-500/20 focus:outline-none dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200 dark:placeholder:text-slate-500"
                    />
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px]">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/80 dark:border-slate-800 dark:bg-slate-800/50">
                                <th class="px-6 py-4 text-left text-[11px] font-black tracking-widest text-slate-500 uppercase">{{ __('Facility') }}</th>
                                <th class="px-6 py-4 text-left text-[11px] font-black tracking-widest text-slate-500 uppercase">{{ __('Details') }}</th>
                                <th class="px-6 py-4 text-left text-[11px] font-black tracking-widest text-slate-500 uppercase">{{ __('Plans') }}</th>
                                <th class="px-6 py-4 text-left text-[11px] font-black tracking-widest text-slate-500 uppercase">{{ __('Status') }}</th>
                                <th class="px-6 py-4 text-right text-[11px] font-black tracking-widest text-slate-500 uppercase">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!hasFacilities">
                                <td colspan="5" class="px-6 py-16 text-center text-sm font-medium text-slate-500 dark:text-slate-400">
                                    {{ __('No facilities found.') }}
                                </td>
                            </tr>
                            <tr
                                v-for="facility in facilityList"
                                :key="facility.id"
                                class="border-b border-slate-100 transition-colors last:border-b-0 hover:bg-slate-50/70 dark:border-slate-800 dark:hover:bg-slate-800/30"
                            >
                                <td class="px-6 py-4 align-top">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ facility.name }}</p>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <p class="max-w-md text-sm text-slate-500 dark:text-slate-400">
                                        {{ facility.description || __('No details added yet.') }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ facility.plans_count || 0 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <span
                                        :class="
                                            facility.is_default
                                                ? 'bg-primary-100 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300'
                                                : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                        "
                                        class="inline-flex rounded-lg px-2.5 py-1 text-xs font-bold"
                                    >
                                        {{ facility.is_default ? __('Default') : __('Custom') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <div class="flex items-center justify-end gap-2">
                                        <AppTooltip :text="getEditDisabledReason(facility) ? __(getEditDisabledReason(facility)) : ''">
                                            <button
                                                @click="openEditModal(facility)"
                                                :disabled="!!getEditDisabledReason(facility)"
                                                class="inline-flex h-9 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 transition-colors disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                                :class="!getEditDisabledReason(facility) ? 'hover:bg-slate-50 dark:hover:bg-slate-700' : ''"
                                            >
                                                <Edit3 :size="14" /> {{ __('Edit') }}
                                            </button>
                                        </AppTooltip>

                                        <AppTooltip :text="getDeleteDisabledReason(facility) ? __(getDeleteDisabledReason(facility)) : ''">
                                            <button
                                                @click="confirmDelete(facility)"
                                                :disabled="!!getDeleteDisabledReason(facility)"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl transition-colors disabled:cursor-not-allowed disabled:opacity-40"
                                                :class="
                                                    getDeleteDisabledReason(facility)
                                                        ? 'text-slate-300 dark:text-slate-600'
                                                        : 'text-slate-400 hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-500/10'
                                                "
                                            >
                                                <Trash2 :size="16" class="pointer-events-none" />
                                            </button>
                                        </AppTooltip>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="hasFacilities && paginationLinks.length > 3"
                    class="flex items-center justify-between border-t border-slate-200 px-6 py-4 dark:border-slate-800"
                >
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ __('Page') }} {{ facilities.current_page }} / {{ facilities.last_page }}
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, index) in paginationLinks"
                            :key="index"
                            :href="link.url || '#'"
                            class="rounded-lg px-3 py-1 text-xs font-semibold transition-colors"
                            :class="
                                link.active
                                    ? 'bg-primary-600 text-white'
                                    : 'text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'
                            "
                            :preserve-state="true"
                            :preserve-scroll="true"
                        >
                            <span v-html="link.label"></span>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="flex items-start gap-4 rounded-[2rem] border border-primary-100 bg-primary-50 p-6 dark:border-primary-500/20 dark:bg-primary-500/5">
                <div class="rounded-xl bg-primary-100 p-2 text-primary-600 dark:bg-primary-500/20 dark:text-primary-500">
                    <AlertCircle :size="20" />
                </div>
                <div>
                    <h4 class="text-sm font-black tracking-tight text-primary-900 uppercase dark:text-primary-200">{{ __('Facility Assignment Rule') }}</h4>
                    <p class="mt-1 text-xs leading-relaxed font-medium text-primary-700 dark:text-primary-400">
                        {{ __('Facilities assigned to plans cannot be deleted until those plans are updated.') }}
                    </p>
                </div>
            </div>
        </div>

        <FacilityFormModal
            :show="showFormModal"
            :facility="editingFacility"
            @close="showFormModal = false"
        />

        <ConfirmationModal
            :show="showDeleteModal"
            :title="__('Delete Facility?')"
            :message="__('Are you sure you want to delete this facility? This action cannot be undone.')"
            :confirmText="__('Yes, Delete Facility')"
            :cancelText="__('Cancel')"
            type="danger"
            @close="showDeleteModal = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
