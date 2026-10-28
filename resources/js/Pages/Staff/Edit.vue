<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    staff: Object,
    roles: Array,
});

const form = useForm({
    name: props.staff.name,
    email: props.staff.email,
    role_id: props.staff.roles[0]?.id || '',
    is_active: props.staff.is_active,
    password: '',
    password_confirmation: '',
});

const submit = () => form.put(route('staff.update', props.staff.id));
</script>

<template>
    <AppLayout>
        <div class="row justify-content-center mt-3">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header app-page-form-header d-flex justify-content-between align-items-center py-3">
                        <div>
                            <h1 class="app-page-form-title mb-0">Edit staff member</h1>
                            <p class="app-page-description">Update account details, access role, or status.</p>
                        </div>
                        <Link :href="route('staff.index')" class="btn btn-sm btn-outline-secondary">Back to staff</Link>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label for="staff-name" class="form-label">Full name</label>
                                <input id="staff-name" v-model="form.name" class="form-control" :class="{ 'is-invalid': form.errors.name }" autocomplete="name" required>
                                <div class="invalid-feedback">{{ form.errors.name }}</div>
                            </div>
                            <div class="mb-3">
                                <label for="staff-email" class="form-label">Email address</label>
                                <input id="staff-email" v-model="form.email" type="email" class="form-control" :class="{ 'is-invalid': form.errors.email }" autocomplete="email" required>
                                <div class="invalid-feedback">{{ form.errors.email }}</div>
                            </div>
                            <div class="mb-3">
                                <label for="staff-role" class="form-label">Role</label>
                                <select id="staff-role" v-model="form.role_id" class="form-select" :class="{ 'is-invalid': form.errors.role_id }" required>
                                    <option value="" disabled>Select a role</option>
                                    <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                                </select>
                                <div class="invalid-feedback">{{ form.errors.role_id }}</div>
                            </div>
                            <div class="form-check form-switch mb-4">
                                <input id="staff-active" v-model="form.is_active" class="form-check-input" type="checkbox">
                                <label for="staff-active" class="form-check-label">Account active</label>
                            </div>
                            <h6 class="border-bottom pb-2">Change password <span class="text-muted fw-normal">(optional)</span></h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="staff-password" class="form-label">New password</label>
                                    <input id="staff-password" v-model="form.password" type="password" class="form-control" :class="{ 'is-invalid': form.errors.password }" autocomplete="new-password" minlength="8">
                                    <div class="invalid-feedback">{{ form.errors.password }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="staff-password-confirmation" class="form-label">Confirm new password</label>
                                    <input id="staff-password-confirmation" v-model="form.password_confirmation" type="password" class="form-control" autocomplete="new-password" minlength="8">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2 mt-2">
                                <Link :href="route('staff.index')" class="btn btn-outline-secondary">Cancel</Link>
                                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
                                    Save changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
