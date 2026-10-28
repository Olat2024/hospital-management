<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { confirmAction } from '@/Composables/useSweetAlert';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    avatar: null,
});

const preview = ref(user.avatar_url || null);
const fileInput = ref(null);
const photoDirty = ref(false);

const initials = computed(() =>
    (user.name || '?')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('')
);

watch(
    () => form.avatar,
    (file) => {
        if (!file) return;
        photoDirty.value = true;
        preview.value = URL.createObjectURL(file);
    }
);

const pickPhoto = () => fileInput.value?.click();

const submit = () => {
    form.transform((data) => ({ ...data, _method: 'patch' })).post(route('profile.update'), {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            form.reset('avatar');
            photoDirty.value = false;
            preview.value = usePage().props.auth.user.avatar_url;
        },
    });
};

const password = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submitPassword = () => {
    password.put(route('password.update'), {
        preserveScroll: true,
        onFinish: () => password.reset(),
    });
};

const destroy = useForm({ password: '' });

const deleteAccount = async () => {
    const confirmed = await confirmAction({
        title: 'Delete your account permanently?',
        text: 'This will permanently remove your account and cannot be undone.',
        confirmButtonText: 'Delete account',
    });

    if (confirmed) {
        destroy.delete(route('profile.destroy'), {
            preserveScroll: true,
            onFinish: () => destroy.reset(),
        });
    }
};
</script>

<template>
    <AppLayout>
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="app-page-header">
                    <div class="app-page-header-content">
                        <div class="app-page-eyebrow">Account settings</div>
                        <h1 class="app-page-title">My Profile</h1>
                        <p class="app-page-description">Manage your personal information and profile photo.</p>
                    </div>
                </div>

                <!-- Photo + account details -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Profile Photo &amp; Details</h5>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="row">
                                <div class="col-md-4 text-center mb-4 mb-md-0">
                                <img
                                    v-if="preview"
                                    :src="preview"
                                    alt="Profile photo"
                                    class="rounded-circle shadow"
                                    style="width: 150px; height: 150px; object-fit: cover;"
                                >
                                <div
                                    v-else
                                    class="rounded-circle bg-primary text-white mx-auto d-flex align-items-center justify-content-center shadow"
                                    style="width: 150px; height: 150px; font-size: 3rem;"
                                >
                                    {{ initials }}
                                </div>

                                <input
                                    ref="fileInput"
                                    type="file"
                                    class="d-none"
                                    accept="image/png,image/jpeg,image/webp"
                                    @change="form.avatar = $event.target.files[0] || null"
                                >
                                <div class="d-grid gap-2 mt-3">
                                    <button type="button" class="btn btn-outline-primary btn-sm" @click="pickPhoto">
                                        <i class="bi bi-camera me-1"></i> Choose photo
                                    </button>
                                    <small class="text-muted">PNG or JPG, up to 2 MB.</small>
                                </div>
                                <p class="text-danger small mt-2 mb-0" v-if="form.errors.avatar">
                                    {{ form.errors.avatar }}
                                </p>
                            </div>

                            <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label" for="name">Full name</label>
                                        <input id="name" type="text" class="form-control" :class="{ 'is-invalid': form.errors.name }" v-model="form.name" required>
                                        <div class="invalid-feedback" v-if="form.errors.name">{{ form.errors.name }}</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="email">Email address</label>
                                        <input id="email" type="email" class="form-control" :class="{ 'is-invalid': form.errors.email }" v-model="form.email" required>
                                        <div class="invalid-feedback" v-if="form.errors.email">{{ form.errors.email }}</div>
                                    </div>

                                    <div
                                        class="alert alert-warning py-2"
                                        v-if="mustVerifyEmail && user.email_verified_at === null"
                                    >
                                        Your email address is unverified.
                                        <a href="#" @click.prevent="$inertia.post(route('verification.send'))" class="alert-link">Resend verification email</a>.
                                    </div>

                                    <div class="d-flex align-items-center gap-3">
                                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                            Save profile
                                        </button>
                                        <span class="text-success" v-if="form.recentlySuccessful && !photoDirty">Saved.</span>
                                        <span class="text-success" v-if="form.recentlySuccessful && photoDirty">
                                            {{ status === 'profile-updated' ? 'Photo updated.' : 'Saved.' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Password -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submitPassword" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="current_password">Current password</label>
                                <input id="current_password" type="password" class="form-control"
                                       :class="{ 'is-invalid': password.errors.current_password }"
                                       v-model="password.current_password" required>
                                <div class="invalid-feedback" v-if="password.errors.current_password">
                                    {{ password.errors.current_password }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="password">New password</label>
                                <input id="password" type="password" class="form-control"
                                       :class="{ 'is-invalid': password.errors.password }"
                                       v-model="password.password" required>
                                <div class="invalid-feedback" v-if="password.errors.password">
                                    {{ password.errors.password }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="password_confirmation">Confirm new password</label>
                                <input id="password_confirmation" type="password" class="form-control"
                                       v-model="password.password_confirmation" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary" :disabled="password.processing">
                                    Update password
                                </button>
                                <span class="text-success ms-2" v-if="password.recentlySuccessful">Saved.</span>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danger zone -->
                <div class="card shadow-sm border-danger">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 text-danger">Delete Account</h5>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="deleteAccount" class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label" for="delete_password">Confirm with your password</label>
                                <input id="delete_password" type="password" class="form-control"
                                       :class="{ 'is-invalid': destroy.errors.password }"
                                       v-model="destroy.password" required>
                                <div class="invalid-feedback" v-if="destroy.errors.password">
                                    {{ destroy.errors.password }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-outline-danger w-100" :disabled="destroy.processing">
                                    Delete my account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
