<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    familyCards: Array,
    selectedFamilyCard: Object,
});

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
    family_card_id: props.selectedFamilyCard?.id || '',
    family_relationship: '',
    is_ems_patient: false,
});

const submit = () => form.post(route('patients.store'));
</script>

<template>
    <AppLayout>
        <div class="clinical-page">
            <header class="clinical-page-heading">
                <div>
                    <div class="clinical-eyebrow">Patient directory</div>
                    <h1 class="clinical-page-title">Register an individual patient</h1>
                    <p class="clinical-page-subtitle">Create the patient's personal and contact record.</p>
                </div>
                <Link :href="route('patients.create')" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Registration options
                </Link>
            </header>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <section class="card clinical-form-card">
                        <div class="card-header">
                            <h2 class="h5 mb-0"><i class="bi bi-person-plus me-2"></i>Patient information</h2>
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="submit">
                                <div v-if="selectedFamilyCard" class="alert alert-info d-flex align-items-start gap-2" role="status">
                                    <i class="bi bi-people-fill mt-1"></i>
                                    <div>
                                        This patient will be added to <strong>{{ selectedFamilyCard.family_name }}</strong>
                                        family card (<code>{{ selectedFamilyCard.card_number }}</code>).
                                    </div>
                                </div>
                                <h3 class="h6 text-uppercase text-muted fw-bold mb-3">Personal details</h3>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First name</label>
                                        <input id="first_name" v-model="form.first_name" class="form-control" :class="{ 'is-invalid': form.errors.first_name }" autocomplete="given-name">
                                        <div class="invalid-feedback">{{ form.errors.first_name }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Surname</label>
                                        <input id="last_name" v-model="form.last_name" class="form-control" :class="{ 'is-invalid': form.errors.last_name }" autocomplete="family-name">
                                        <div class="invalid-feedback">{{ form.errors.last_name }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="dob" class="form-label">Date of birth</label>
                                        <input id="dob" v-model="form.dob" type="date" class="form-control" :class="{ 'is-invalid': form.errors.dob }">
                                        <div class="invalid-feedback">{{ form.errors.dob }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select id="gender" v-model="form.gender" class="form-select" :class="{ 'is-invalid': form.errors.gender }">
                                            <option value="" disabled>Select...</option>
                                            <option>Male</option>
                                            <option>Female</option>
                                            <option>Other</option>
                                        </select>
                                        <div class="invalid-feedback">{{ form.errors.gender }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="blood_group" class="form-label">Blood group</label>
                                        <select id="blood_group" v-model="form.blood_group" class="form-select" :class="{ 'is-invalid': form.errors.blood_group }">
                                            <option value="">Unknown</option>
                                            <option v-for="group in ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']" :key="group">{{ group }}</option>
                                        </select>
                                        <div class="invalid-feedback">{{ form.errors.blood_group }}</div>
                                    </div>
                                </div>

                                <h3 class="h6 text-uppercase text-muted fw-bold border-bottom pb-2 mb-3">Contact details</h3>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone number</label>
                                        <input id="phone" v-model="form.phone" class="form-control" :class="{ 'is-invalid': form.errors.phone }" autocomplete="tel">
                                        <div class="invalid-feedback">{{ form.errors.phone }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email <span class="text-muted">(optional)</span></label>
                                        <input id="email" v-model="form.email" type="email" class="form-control" :class="{ 'is-invalid': form.errors.email }" autocomplete="email">
                                        <div class="invalid-feedback">{{ form.errors.email }}</div>
                                    </div>
                                    <div class="col-12">
                                        <label for="address" class="form-label">Street address</label>
                                        <textarea id="address" v-model="form.address" rows="2" class="form-control" :class="{ 'is-invalid': form.errors.address }"></textarea>
                                        <div class="invalid-feedback">{{ form.errors.address }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="city" class="form-label">City</label>
                                        <input id="city" v-model="form.city" class="form-control" :class="{ 'is-invalid': form.errors.city }">
                                        <div class="invalid-feedback">{{ form.errors.city }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="state" class="form-label">State</label>
                                        <input id="state" v-model="form.state" class="form-control" :class="{ 'is-invalid': form.errors.state }">
                                        <div class="invalid-feedback">{{ form.errors.state }}</div>
                                    </div>
                                </div>

                                <h3 class="h6 text-uppercase text-muted fw-bold border-bottom pb-2 mb-3">Clinical and emergency information</h3>
                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <label for="allergies" class="form-label">Known allergies</label>
                                        <textarea id="allergies" v-model="form.allergies" rows="2" class="form-control" :class="{ 'is-invalid': form.errors.allergies }"></textarea>
                                        <div class="invalid-feedback">{{ form.errors.allergies }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="emergency_contact_name" class="form-label">Emergency contact name</label>
                                        <input id="emergency_contact_name" v-model="form.emergency_contact_name" class="form-control" :class="{ 'is-invalid': form.errors.emergency_contact_name }">
                                        <div class="invalid-feedback">{{ form.errors.emergency_contact_name }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="emergency_contact_phone" class="form-label">Emergency contact phone</label>
                                        <input id="emergency_contact_phone" v-model="form.emergency_contact_phone" class="form-control" :class="{ 'is-invalid': form.errors.emergency_contact_phone }">
                                        <div class="invalid-feedback">{{ form.errors.emergency_contact_phone }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="insurance_provider" class="form-label">Insurance provider</label>
                                        <input id="insurance_provider" v-model="form.insurance_provider" class="form-control" :class="{ 'is-invalid': form.errors.insurance_provider }">
                                        <div class="invalid-feedback">{{ form.errors.insurance_provider }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="insurance_number" class="form-label">Insurance number</label>
                                        <input id="insurance_number" v-model="form.insurance_number" class="form-control" :class="{ 'is-invalid': form.errors.insurance_number }">
                                        <div class="invalid-feedback">{{ form.errors.insurance_number }}</div>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="family_card_id" class="form-label">Add patient to family card <span class="text-muted">(optional)</span></label>
                                        <select id="family_card_id" v-model="form.family_card_id" class="form-select" :class="{ 'is-invalid': form.errors.family_card_id }">
                                            <option value="">Do not add to a family card</option>
                                            <option v-for="card in familyCards" :key="card.id" :value="card.id">
                                                {{ card.card_number }} — {{ card.family_name }}
                                            </option>
                                        </select>
                                        <div class="invalid-feedback">{{ form.errors.family_card_id }}</div>
                                        <div class="form-text">Choose a family card here, or use “Add patient” from the family-card list to preselect one.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="family_relationship" class="form-label">Relationship to family</label>
                                        <input id="family_relationship" v-model="form.family_relationship" class="form-control" :class="{ 'is-invalid': form.errors.family_relationship }">
                                        <div class="invalid-feedback">{{ form.errors.family_relationship }}</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input id="is_ems_patient" v-model="form.is_ems_patient" type="checkbox" class="form-check-input">
                                            <label for="is_ems_patient" class="form-check-label">Flag this patient for EMS support</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary" @click="form.reset()">Clear</button>
                                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                        Register patient
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
