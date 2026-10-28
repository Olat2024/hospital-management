<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    first_name: '',
    last_name: '',
    dob: '',
    gender: '',
    blood_group: '',
    phone: '',
    email: '',
    address: '',
    city: 'Ijebu Ode',
    state: 'Ogun State',
    allergies: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    insurance_provider: '',
    insurance_number: '',
});

const submit = () => {
    form.post(route('patients.store'));
};
</script>

<template>
    <AppLayout>
        <div class="clinical-page">
            <header class="clinical-page-heading">
                <div>
                    <div class="clinical-eyebrow">Patient directory</div>
                    <h1 class="clinical-page-title">Register a patient</h1>
                    <p class="clinical-page-subtitle">Create a patient record and add essential contact and care details.</p>
                </div>
                <Link :href="route('patients.index')" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to patients
                </Link>
            </header>
            <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card clinical-form-card">
                    <div class="card-header">
                        <h2 class="h5 mb-0"><i class="bi bi-person-plus me-2"></i>Patient information</h2>
                    </div>
                    
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <h3 class="h6 text-uppercase text-muted fw-bold mb-3">Personal details</h3>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" :class="{ 'is-invalid': form.errors.first_name }" id="first_name" v-model="form.first_name">
                                    <div class="invalid-feedback" v-if="form.errors.first_name">{{ form.errors.first_name }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" :class="{ 'is-invalid': form.errors.last_name }" id="last_name" v-model="form.last_name">
                                    <div class="invalid-feedback" v-if="form.errors.last_name">{{ form.errors.last_name }}</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" :class="{ 'is-invalid': form.errors.dob }" id="dob" v-model="form.dob">
                                    <div class="invalid-feedback" v-if="form.errors.dob">{{ form.errors.dob }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" :class="{ 'is-invalid': form.errors.gender }" id="gender" v-model="form.gender">
                                        <option value="" disabled>Select...</option>
                                        <option>Male</option>
                                        <option>Female</option>
                                        <option>Other</option>
                                    </select>
                                    <div class="invalid-feedback" v-if="form.errors.gender">{{ form.errors.gender }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="blood_group" class="form-label">Blood Group</label>
                                    <select class="form-select" :class="{ 'is-invalid': form.errors.blood_group }" id="blood_group" v-model="form.blood_group">
                                        <option value="">Unknown</option>
                                        <option>A+</option>
                                        <option>A-</option>
                                        <option>B+</option>
                                        <option>B-</option>
                                        <option>AB+</option>
                                        <option>AB-</option>
                                        <option>O+</option>
                                        <option>O-</option>
                                    </select>
                                    <div class="invalid-feedback" v-if="form.errors.blood_group">{{ form.errors.blood_group }}</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" :class="{ 'is-invalid': form.errors.phone }" id="phone" v-model="form.phone">
                                    <div class="invalid-feedback" v-if="form.errors.phone">{{ form.errors.phone }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email <span class="text-muted">(optional)</span></label>
                                    <input type="email" class="form-control" :class="{ 'is-invalid': form.errors.email }" id="email" v-model="form.email">
                                    <div class="invalid-feedback" v-if="form.errors.email">{{ form.errors.email }}</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Street Address</label>
                                <textarea class="form-control" :class="{ 'is-invalid': form.errors.address }" id="address" rows="2" v-model="form.address"></textarea>
                                <div class="invalid-feedback" v-if="form.errors.address">{{ form.errors.address }}</div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" :class="{ 'is-invalid': form.errors.city }" id="city" v-model="form.city">
                                    <div class="invalid-feedback" v-if="form.errors.city">{{ form.errors.city }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="state" class="form-label">State</label>
                                    <input type="text" class="form-control" :class="{ 'is-invalid': form.errors.state }" id="state" v-model="form.state">
                                    <div class="invalid-feedback" v-if="form.errors.state">{{ form.errors.state }}</div>
                                </div>
                            </div>

                            <h3 class="h6 text-uppercase text-muted fw-bold border-bottom pb-2 mb-3">Additional information <span class="small fw-normal">(optional)</span></h3>

                            <div class="mb-3">
                                <label for="allergies" class="form-label">Known Allergies</label>
                                <textarea class="form-control" :class="{ 'is-invalid': form.errors.allergies }" id="allergies" rows="2" placeholder="e.g. Penicillin, Peanuts" v-model="form.allergies"></textarea>
                                <div class="invalid-feedback" v-if="form.errors.allergies">{{ form.errors.allergies }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                                    <input type="text" class="form-control" :class="{ 'is-invalid': form.errors.emergency_contact_name }" id="emergency_contact_name" v-model="form.emergency_contact_name">
                                    <div class="invalid-feedback" v-if="form.errors.emergency_contact_name">{{ form.errors.emergency_contact_name }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                                    <input type="text" class="form-control" :class="{ 'is-invalid': form.errors.emergency_contact_phone }" id="emergency_contact_phone" v-model="form.emergency_contact_phone">
                                    <div class="invalid-feedback" v-if="form.errors.emergency_contact_phone">{{ form.errors.emergency_contact_phone }}</div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="insurance_provider" class="form-label">Insurance Provider</label>
                                    <input type="text" class="form-control" :class="{ 'is-invalid': form.errors.insurance_provider }" id="insurance_provider" v-model="form.insurance_provider">
                                    <div class="invalid-feedback" v-if="form.errors.insurance_provider">{{ form.errors.insurance_provider }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="insurance_number" class="form-label">Insurance Number</label>
                                    <input type="text" class="form-control" :class="{ 'is-invalid': form.errors.insurance_number }" id="insurance_number" v-model="form.insurance_number">
                                    <div class="invalid-feedback" v-if="form.errors.insurance_number">{{ form.errors.insurance_number }}</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary" @click="form.reset()">Clear</button>
                                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    Register Patient
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </AppLayout>
</template>
