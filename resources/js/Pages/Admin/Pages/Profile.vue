<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import admin from '@/routes/admin';

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const page = usePage();
const user = page.props.auth.user;
const preview = ref(user.profile_image ? '/storage/' + user.profile_image : null);
const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    _method: 'PATCH',
    name: user.name,
    email: user.email,
    current_password: '',
    password: '',
    password_confirmation: '',
    profile_image: null as File | null,
});

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.profile_image = file;
        preview.value = URL.createObjectURL(file);
    }
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const submit = () => {
    form.post(admin.profile.update.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('current_password', 'password', 'password_confirmation');
            // Update preview if successful ? The page reload will handle it, but inertia might just update props.
            // If we return back(), props should update.
        },
    });
};
</script>

<template>
    <AdminLayout>
        <div class="animate-fade-in mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('Profile Settings') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Update your account information and password.') }}</p>
            </div>

            <div class="grid gap-6">
                <!-- Profile Information -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="mb-6 text-base font-bold text-slate-900 dark:text-white">{{ __('Personal Information') }}</h3>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Image Upload -->
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
                                    @click="triggerFileInput"
                                    class="absolute inset-0 flex cursor-pointer items-center justify-center rounded-full bg-black/40 text-white opacity-0 transition-opacity group-hover:opacity-100"
                                >
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                                        ></path>
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                                        ></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white">{{ __('Profile Photo') }}</h4>
                                <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">{{ __('Accepts JPG, PNG or GIF. Max 2MB.') }}</p>
                                <button type="button" @click="triggerFileInput" class="text-xs font-bold text-[var(--brand-primary)] hover:underline">
                                    {{ __('Change Photo') }}
                                </button>
                                <input type="file" ref="fileInput" class="hidden" @change="onFileChange" accept="image/*" />
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
                                    placeholder="Your Name"
                                />
                                <div v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</div>
                            </div>

                            <div class="space-y-1">
                                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{
                                    __('Email Address')
                                }}</label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 transition-all outline-none focus:border-transparent focus:ring-2 focus:ring-[var(--brand-primary)] dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                    placeholder="name@example.com"
                                />
                                <div v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</div>
                            </div>
                        </div>

                        <div class="my-6 border-t border-slate-100 pt-6 dark:border-slate-800">
                            <h3 class="mb-4 text-base font-bold text-slate-900 dark:text-white">{{ __('Change Password') }}</h3>
                            <div class="max-w-md space-y-4">
                                <div class="space-y-1">
                                    <label for="current_password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{
                                        __('Current Password')
                                    }}</label>
                                    <div class="relative">
                                        <input
                                            id="current_password"
                                            v-model="form.current_password"
                                            :type="showCurrentPassword ? 'text' : 'password'"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 pr-10 text-slate-900 transition-all outline-none focus:border-transparent focus:ring-2 focus:ring-[var(--brand-primary)] dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                            placeholder="Enter current password"
                                        />
                                        <button
                                            type="button"
                                            @click="showCurrentPassword = !showCurrentPassword"
                                            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                        >
                                            <svg v-if="!showCurrentPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    <div v-if="form.errors.current_password" class="text-xs text-red-500">{{ form.errors.current_password }}</div>
                                </div>

                                <div class="space-y-1">
                                    <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{
                                        __('New Password')
                                    }}</label>
                                    <div class="relative">
                                        <input
                                            id="password"
                                            v-model="form.password"
                                            :type="showNewPassword ? 'text' : 'password'"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 pr-10 text-slate-900 transition-all outline-none focus:border-transparent focus:ring-2 focus:ring-[var(--brand-primary)] dark:border-slate-700 dark:bg-slate-800 dark:text-white"
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
                                    <div v-if="form.errors.password" class="text-xs text-red-500">{{ form.errors.password }}</div>
                                </div>

                                <div class="space-y-1">
                                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{
                                        __('Confirm Password')
                                    }}</label>
                                    <div class="relative">
                                        <input
                                            id="password_confirmation"
                                            v-model="form.password_confirmation"
                                            :type="showConfirmPassword ? 'text' : 'password'"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 pr-10 text-slate-900 transition-all outline-none focus:border-transparent focus:ring-2 focus:ring-[var(--brand-primary)] dark:border-slate-700 dark:bg-slate-800 dark:text-white"
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
    </AdminLayout>
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
