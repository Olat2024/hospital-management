<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    roles: Array,
});

const form = useForm({
    name: '',
    email: '',
    role_id: '',
    password: '',
    password_confirmation: '',
});

const submit = () => form.post(route('staff.store'));
</script>

<template>
    <AppLayout>
        <div class="row justify-content-center mt-3">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header app-page-form-header d-flex justify-content-between align-items-center py-3">
                        <div>
                            <h1 class="app-page-form-title mb-0">Add staff member</h1>
                            <p class="app-page-description">Create a login account and assign a staff role.</p>
                        </div>
                        <Link :href="route('staff.index')" class="btn btn-sm btn-outline-secondary">Back to staff</Link>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            Add doctors from the <Link :href="route('doctors.index')">doctor directory</Link> so their clinical profile is created too.
                        </div>
                        <div v-if="roles.length === 0" class="alert alert-warning">
                            No staff roles are available. Add a role in Roles &amp; Permissions before creating a staff account.
                        </div>
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
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="staff-password" class="form-label">Temporary password</label>
                                    <input id="staff-password" v-model="form.password" type="password" class="form-control" :class="{ 'is-invalid': form.errors.password }" autocomplete="new-password" minlength="8" required>
                                    <div class="invalid-feedback">{{ form.errors.password }}</div>
                                    <div class="form-text">At least 8 characters.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="staff-password-confirmation" class="form-label">Confirm password</label>
                                    <input id="staff-password-confirmation" v-model="form.password_confirmation" type="password" class="form-control" autocomplete="new-password" minlength="8" required>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2 mt-2">
                                <Link :href="route('staff.index')" class="btn btn-outline-secondary">Cancel</Link>
                                <button type="submit" class="btn btn-primary" :disabled="form.processing || roles.length === 0">
                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
                                    Add staff member
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
