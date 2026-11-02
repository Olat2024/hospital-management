<!-- resources/js/Pages/Doctors/Create.vue -->
<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    unlinkedDoctorUsers: Array,
});

const form = useForm({
    user_id: '',
    first_name: '',
    last_name: '',
    specialization: '',
    phone: '',
    email: '',
    password: '',
    is_active: true,
});

const submit = () => {
    form.post(route('doctors.store'));
};
</script>

<template>
    <AppLayout>
        <div class="row justify-content-center mt-4">
            <div class="col-md-9">
                <div class="card shadow-sm">
                    <div class="card-header app-page-form-header d-flex justify-content-between align-items-center py-3">
                        <h1 class="app-page-form-title mb-0"><i class="bi bi-person-badge me-2"></i>Add Doctor</h1>
                        <Link :href="route('doctors.index')" class="btn btn-sm btn-outline-secondary">Back to list</Link>
                    </div>

                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <h6 class="border-bottom pb-2 text-muted">Profile</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First name</label>
                                    <input
                                        id="first_name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.first_name }"
                                        v-model="form.first_name"
                                        required
                                    >
                                    <div class="invalid-feedback" v-if="form.errors.first_name">{{ form.errors.first_name }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last name</label>
                                    <input
                                        id="last_name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.last_name }"
                                        v-model="form.last_name"
                                        required
                                    >
                                    <div class="invalid-feedback" v-if="form.errors.last_name">{{ form.errors.last_name }}</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="specialization" class="form-label">Specialization</label>
                                    <input
                                        id="specialization"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.specialization }"
                                        placeholder="e.g. General Medicine"
                                        v-model="form.specialization"
                                        required
                                    >
                                    <div class="invalid-feedback" v-if="form.errors.specialization">{{ form.errors.specialization }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input
                                        id="phone"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.phone }"
                                        v-model="form.phone"
                                        required
                                    >
                                    <div class="invalid-feedback" v-if="form.errors.phone">{{ form.errors.phone }}</div>
                                </div>
                            </div>

                            <div class="mb-4 form-check">
                                <input id="is_active" type="checkbox" class="form-check-input" v-model="form.is_active">
                                <label for="is_active" class="form-check-label">
                                    Active &mdash; accepts new appointments
                                </label>
                            </div>

                            <h6 class="border-bottom pb-2 text-muted">Login Account</h6>
                            <p class="small text-muted">
                                Link an existing Doctor login or create a new account. Linking enables the doctor to save clinical notes under their profile.
                            </p>
                            <div class="mb-3">
                                <label for="doctor-user" class="form-label">Doctor login account</label>
                                <select
                                    id="doctor-user"
                                    v-model="form.user_id"
                                    class="form-select"
                                    :class="{ 'is-invalid': form.errors.user_id }"
                                >
                                    <option value="">Create a new login account</option>
                                    <option v-for="account in props.unlinkedDoctorUsers" :key="account.id" :value="account.id">
                                        {{ account.name }} ({{ account.email }})
                                    </option>
                                </select>
                                <div class="invalid-feedback">{{ form.errors.user_id }}</div>
                            </div>
                            <div v-if="!form.user_id" class="row mb-4">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input
                                        id="email"
                                        type="email"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.email }"
                                        v-model="form.email"
                                        required
                                    >
                                    <div class="invalid-feedback" v-if="form.errors.email">{{ form.errors.email }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password</label>
                                    <input
                                        id="password"
                                        type="password"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.password }"
                                        placeholder="Minimum 8 characters"
                                        v-model="form.password"
                                        required
                                    >
                                    <div class="invalid-feedback" v-if="form.errors.password">{{ form.errors.password }}</div>
                                </div>
                            </div>
                            <div v-else class="alert alert-info py-2 mb-4">
                                This profile will be linked to the selected login account. No new account or password will be created.
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <Link :href="route('doctors.index')" class="btn btn-secondary">Cancel</Link>
                                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    Add Doctor
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
