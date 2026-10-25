<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PlusCircle, Crown, Shield, ShieldCheck, Layers, Users, Trash2, ArrowRight, Plus } from 'lucide-vue-next';
import type { PropType} from 'vue';
import { ref } from 'vue';
import ConfirmationModal from '@/Components/Common/ConfirmationModal.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import admin from '@/routes/admin';

defineProps({
    roles: Array as PropType<any[]>,
});

const getRoleIcon = (roleName: string) => {
    if (roleName.toLowerCase().includes('admin')) return Crown;
    if (roleName.toLowerCase().includes('moderator')) return Shield;
    return ShieldCheck;
};

const getRoleColor = (roleName: string) => {
    if (roleName.toLowerCase().includes('admin')) return 'text-brand-600 bg-brand-50 dark:bg-brand-500/10';
    if (roleName.toLowerCase().includes('moderator')) return 'text-slate-500 bg-slate-50 dark:bg-slate-800';
    return 'text-blue-500 bg-blue-50 dark:bg-blue-900/20';
};

// Deletion Logic
const showDeleteModal = ref(false);
const roleToDelete = ref<any>(null);
const processingDelete = ref(false);

const confirmDelete = (role: any) => {
    roleToDelete.value = role;
    showDeleteModal.value = true;
};

const handleDelete = () => {
    if (!roleToDelete.value) return;

    processingDelete.value = true;
    router.delete(admin.roles.destroy.url(roleToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            roleToDelete.value = null;
        },
        onFinish: () => {
            processingDelete.value = false;
        },
    });
};
</script>

<template>
    <Head :title="__('Access Roles')" />

    <AdminLayout>
        <div class="animate-fade-in space-y-8">
            <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                <div>
                    <h2 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ __('Access Roles') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Manage organizational security boundaries and permissions.') }}
                    </p>
                </div>
                <Link
                    :href="admin.roles.create ? admin.roles.create.url() : '#'"
                    class="flex items-center rounded-2xl bg-brand-600 px-4 py-2 text-sm font-bold text-white shadow-xl shadow-brand-600/20 transition-all hover:bg-brand-700 active:scale-95"
                >
                    <PlusCircle :size="18" class="me-2" /> {{ __('Create New Role') }}
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <!-- Role Card -->
                <div
                    v-for="role in roles"
                    :key="role.id"
                    class="group flex flex-col justify-between rounded-[2rem] border border-slate-200/60 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-slate-700 dark:bg-slate-800"
                >
                    <div>
                        <div class="mb-6 flex items-start justify-between">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl shadow-inner transition-transform group-hover:scale-110"
                                :class="getRoleColor(role.name)"
                            >
                                <component :is="getRoleIcon(role.name)" :size="20" />
                            </div>
                            <div class="relative flex flex-col items-end">
                                <span
                                    class="mt-2 rounded-lg border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-[9px] font-bold tracking-widest text-emerald-600 uppercase dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400"
                                    >{{ __('Active') }}</span
                                >
                            </div>
                        </div>

                        <h3 class="mb-2 text-base font-bold text-slate-900 dark:text-white">{{ role.name }}</h3>
                        <p class="mb-6 line-clamp-2 h-8 text-xs text-slate-500 dark:text-slate-400">
                            {{ role.description || __('No description provided.') }}
                        </p>

                        <div class="mb-6 grid grid-cols-2 gap-4">
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-2 dark:border-slate-800 dark:bg-slate-800/50">
                                <p class="mb-1 flex items-center gap-1 text-[9px] font-bold tracking-widest text-slate-400 uppercase">
                                    <Layers :size="10" /> {{ __('Permissions') }}
                                </p>
                                <p class="text-sm font-black text-brand-600">{{ role.permissions_count || 0 }} {{ __('Active') }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-2 dark:border-slate-800 dark:bg-slate-800/50">
                                <p class="mb-1 flex items-center gap-1 text-[9px] font-bold tracking-widest text-slate-400 uppercase">
                                    <Users :size="10" /> {{ __('Users') }}
                                </p>
                                <p class="text-sm font-black text-blue-500">{{ role.users_count || 0 }} {{ __('Members') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-50 pt-6 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <button
                                v-if="role.name !== 'Super Admin'"
                                @click="confirmDelete(role)"
                                class="group/delete flex items-center text-xs font-bold text-slate-400 transition-colors hover:text-red-500"
                            >
                                <Trash2 :size="12" class="me-1 transition-transform group-hover/delete:scale-110" />
                                {{ __('Delete') }}
                            </button>
                            <span v-else class="text-[9px] font-bold tracking-widest text-slate-400 uppercase">{{ __('System Protected') }}</span>
                        </div>
                        <Link
                            v-if="role.name !== 'Super Admin'"
                            :href="admin.roles.edit ? admin.roles.edit.url(role.id) : '#'"
                            class="flex items-center text-xs font-bold text-brand-600 transition-transform group-hover:translate-x-1 hover:underline rtl:group-hover:-translate-x-1"
                        >
                            {{ __('Edit') }} <ArrowRight :size="10" class="ms-2 rtl:rotate-180" />
                        </Link>
                        <div v-else class="text-[9px] font-bold tracking-widest text-slate-400 uppercase">
                            {{ __('Immutable') }}
                        </div>
                    </div>
                </div>

                <!-- Create New Role Card -->
                <Link
                    :href="admin.roles.create ? admin.roles.create.url() : '#'"
                    class="group flex min-h-[300px] flex-col items-center justify-center rounded-[2rem] border-2 border-dashed border-slate-200 p-5 transition-all hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800/30"
                >
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400 transition-transform group-hover:scale-110 dark:bg-slate-800"
                    >
                        <Plus :size="20" />
                    </div>
                    <p class="mt-4 text-sm font-bold text-slate-400 transition-colors group-hover:text-brand-500">{{ __('Define Custom Role') }}</p>
                    <p class="mt-2 text-[10px] tracking-widest text-slate-300 uppercase">{{ __('New Security Boundary') }}</p>
                </Link>
            </div>
        </div>

        <ConfirmationModal
            :show="showDeleteModal"
            :title="__('Delete Access Role?')"
            :message="__('Are you sure you want to delete this role? This action cannot be undone and may affect users assigned to this role.')"
            :confirmText="__('Yes, Delete Role')"
            :cancelText="__('No, Cancel')"
            type="danger"
            :processing="processingDelete"
            @close="showDeleteModal = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
