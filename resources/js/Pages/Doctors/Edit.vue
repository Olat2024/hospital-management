<!-- resources/js/Pages/Doctors/Edit.vue -->
<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    doctor: Object
});

const form = useForm({
    first_name: props.doctor.first_name,
    last_name: props.doctor.last_name,
    specialization: props.doctor.specialization,
    phone: props.doctor.phone,
    email: props.doctor.user?.email || '',
    password: '',
    is_active: props.doctor.is_active,
});

const submit = () => {
    form.put(route('doctors.update', props.doctor.id));
};
</script>

<template>
    <AppLayout>
        <div class="row justify-content-center mt-4">
            <div class="col-md-9">
                <div class="card shadow-sm">
                    <div class="card-header app-page-form-header d-flex justify-content-between align-items-center py-3">
                        <h1 class="app-page-form-title mb-0">
                            <i class="bi bi-person-badge me-2"></i>
                            Edit Dr. {{ doctor.first_name }} {{ doctor.last_name }}
                        </h1>
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
                            <div class="row mb-4">
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
                                    <label for="password" class="form-label">New password</label>
                                    <input
                                        id="password"
                                        type="password"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.password }"
                                        placeholder="Leave blank to keep current"
                                        v-model="form.password"
                                    >
                                    <div class="invalid-feedback" v-if="form.errors.password">{{ form.errors.password }}</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <Link :href="route('doctors.index')" class="btn btn-secondary">Cancel</Link>
                                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
