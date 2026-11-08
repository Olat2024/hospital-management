<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    patient: Object,
    familyCards: Array,
});

const form = useForm({
    first_name: props.patient.first_name || '',
    last_name: props.patient.last_name || '',
    dob: props.patient.dob ? String(props.patient.dob).slice(0, 10) : '',
    gender: props.patient.gender || '',
    blood_group: props.patient.blood_group || '',
    phone: props.patient.phone || '',
    email: props.patient.email || '',
    address: props.patient.address || '',
    allergies: props.patient.allergies || '',
    emergency_contact_name: props.patient.emergency_contact_name || '',
    emergency_contact_phone: props.patient.emergency_contact_phone || '',
    insurance_provider: props.patient.insurance_provider || '',
    insurance_number: props.patient.insurance_number || '',
    family_card_id: props.patient.family_card_id || '',
    family_relationship: props.patient.family_relationship || '',
    pregnancy_status: props.patient.pregnancy_status || '',
    is_ems_patient: Boolean(props.patient.is_ems_patient),
    pregnancy_notes: props.patient.pregnancy_notes || '',
});

const submit = () => form.put(route('patients.update', props.patient.id));
</script>

<template>
    <AppLayout>
        <div class="clinical-page">
            <header class="clinical-page-heading">
                <div>
                    <div class="clinical-eyebrow">Patient directory</div>
                    <h1 class="clinical-page-title">Edit patient</h1>
                    <p class="clinical-page-subtitle">
                        {{ patient.first_name }} {{ patient.last_name }} · <code>{{ patient.mrn }}</code>
                    </p>
                </div>
                <Link :href="route('patients.show', patient.id)" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Patient chart
                </Link>
            </header>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <section class="card clinical-form-card">
                        <div class="card-header">
                            <h2 class="h5 mb-0"><i class="bi bi-pencil-square me-2"></i>Correct patient details</h2>
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="submit">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First name</label>
                                        <input id="first_name" v-model="form.first_name" class="form-control" :class="{ 'is-invalid': form.errors.first_name }" required>
                                        <div class="invalid-feedback">{{ form.errors.first_name }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Surname</label>
                                        <input id="last_name" v-model="form.last_name" class="form-control" :class="{ 'is-invalid': form.errors.last_name }" required>
                                        <div class="invalid-feedback">{{ form.errors.last_name }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="dob" class="form-label">Date of birth</label>
                                        <input id="dob" v-model="form.dob" type="date" class="form-control" :class="{ 'is-invalid': form.errors.dob }" required>
                                        <div class="invalid-feedback">{{ form.errors.dob }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select id="gender" v-model="form.gender" class="form-select" :class="{ 'is-invalid': form.errors.gender }" required>
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
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input id="phone" v-model="form.phone" class="form-control" :class="{ 'is-invalid': form.errors.phone }" required>
                                        <div class="invalid-feedback">{{ form.errors.phone }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input id="email" v-model="form.email" type="email" class="form-control" :class="{ 'is-invalid': form.errors.email }">
                                        <div class="invalid-feedback">{{ form.errors.email }}</div>
                                    </div>
                                    <div class="col-12">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea id="address" v-model="form.address" rows="2" class="form-control" :class="{ 'is-invalid': form.errors.address }" required></textarea>
                                        <div class="invalid-feedback">{{ form.errors.address }}</div>
                                    </div>
                                    <div class="col-12">
                                        <label for="allergies" class="form-label">Known allergies</label>
                                        <textarea id="allergies" v-model="form.allergies" rows="2" class="form-control" :class="{ 'is-invalid': form.errors.allergies }"></textarea>
                                        <div class="invalid-feedback">{{ form.errors.allergies }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="emergency_contact_name" class="form-label">Emergency contact</label>
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
                                        <label for="family_card_id" class="form-label">Family card</label>
                                        <select id="family_card_id" v-model="form.family_card_id" class="form-select" :class="{ 'is-invalid': form.errors.family_card_id }">
                                            <option value="">No family card</option>
                                            <option v-for="card in familyCards" :key="card.id" :value="card.id">
                                                {{ card.card_number }} — {{ card.family_name }}
                                            </option>
                                        </select>
                                        <div class="invalid-feedback">{{ form.errors.family_card_id }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="family_relationship" class="form-label">Relationship to family</label>
                                        <input id="family_relationship" v-model="form.family_relationship" class="form-control" :class="{ 'is-invalid': form.errors.family_relationship }">
                                        <div class="invalid-feedback">{{ form.errors.family_relationship }}</div>
                                    </div>
                                    <div v-if="patient.pregnancy_status !== undefined" class="col-md-6">
                                        <label for="pregnancy_status" class="form-label">Maternal status</label>
                                        <select id="pregnancy_status" v-model="form.pregnancy_status" class="form-select" :class="{ 'is-invalid': form.errors.pregnancy_status }">
                                            <option value="">Not specified</option>
                                            <option>Not Pregnant</option>
                                            <option>Pregnant</option>
                                            <option>High Risk Pregnancy</option>
                                            <option>Postpartum</option>
                                        </select>
                                        <div class="invalid-feedback">{{ form.errors.pregnancy_status }}</div>
                                    </div>
                                    <div v-if="patient.is_ems_patient !== undefined" class="col-md-6 d-flex align-items-end">
                                        <div class="form-check mb-2">
                                            <input id="is_ems_patient" v-model="form.is_ems_patient" type="checkbox" class="form-check-input">
                                            <label for="is_ems_patient" class="form-check-label">Flag for EMS support</label>
                                        </div>
                                    </div>
                                    <div v-if="patient.pregnancy_notes !== undefined" class="col-12">
                                        <label for="pregnancy_notes" class="form-label">Maternal / emergency notes</label>
                                        <textarea id="pregnancy_notes" v-model="form.pregnancy_notes" rows="2" class="form-control" :class="{ 'is-invalid': form.errors.pregnancy_notes }"></textarea>
                                        <div class="invalid-feedback">{{ form.errors.pregnancy_notes }}</div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <Link :href="route('patients.show', patient.id)" class="btn btn-outline-secondary">Cancel</Link>
                                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                        Save changes
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
