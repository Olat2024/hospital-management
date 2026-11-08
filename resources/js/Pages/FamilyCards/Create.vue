<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    family_name: '',
    contact_name: '',
    phone: '',
    address: '',
});

const submit = () => form.post(route('family-cards.store'));
</script>

<template>
    <AppLayout>
        <div class="clinical-page">
            <header class="clinical-page-heading">
                <div>
                    <div class="clinical-eyebrow">Patient directory</div>
                    <h1 class="clinical-page-title">Register a family card</h1>
                    <p class="clinical-page-subtitle">Create one household card, then assign it to patients as they are registered.</p>
                </div>
                <Link :href="route('patients.create')" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Registration options
                </Link>
            </header>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <section class="card clinical-form-card">
                        <div class="card-header">
                            <h2 class="h5 mb-0"><i class="bi bi-people me-2"></i>Family information</h2>
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="submit">
                                <div class="mb-3">
                                    <label for="family_name" class="form-label">Family name</label>
                                    <input id="family_name" v-model="form.family_name" class="form-control" :class="{ 'is-invalid': form.errors.family_name }" required>
                                    <div class="invalid-feedback">{{ form.errors.family_name }}</div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="contact_name" class="form-label">Primary contact</label>
                                        <input id="contact_name" v-model="form.contact_name" class="form-control" :class="{ 'is-invalid': form.errors.contact_name }">
                                        <div class="invalid-feedback">{{ form.errors.contact_name }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Family phone</label>
                                        <input id="phone" v-model="form.phone" class="form-control" :class="{ 'is-invalid': form.errors.phone }">
                                        <div class="invalid-feedback">{{ form.errors.phone }}</div>
                                    </div>
                                    <div class="col-12">
                                        <label for="address" class="form-label">Family address</label>
                                        <textarea id="address" v-model="form.address" rows="3" class="form-control" :class="{ 'is-invalid': form.errors.address }"></textarea>
                                        <div class="invalid-feedback">{{ form.errors.address }}</div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <Link :href="route('family-cards.index')" class="btn btn-link px-0">View family cards</Link>
                                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                        Create family card
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
