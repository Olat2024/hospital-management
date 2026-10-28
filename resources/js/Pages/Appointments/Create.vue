<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    patients: Array,
    doctors: Array
});

const form = useForm({
    patient_id: '',
    doctor_id: '',
    appointment_date: '',
    appointment_time: '',
    notes: ''
});

const submit = () => {
    form.post(route('appointments.store'));
};
</script>

<template>
    <AppLayout>
        <div class="clinical-page">
            <header class="clinical-page-heading">
                <div>
                    <div class="clinical-eyebrow">Appointment schedule</div>
                    <h1 class="clinical-page-title">Book an appointment</h1>
                    <p class="clinical-page-subtitle">Choose a patient, clinician, and time for the visit.</p>
                </div>
                <Link :href="route('appointments.index')" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to schedule
                </Link>
            </header>
            <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card clinical-form-card">
                    <div class="card-header">
                        <h2 class="h5 mb-0"><i class="bi bi-calendar-plus me-2"></i>Appointment details</h2>
                    </div>
                    
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="patient_id" class="form-label">Select Patient</label>
                                    <select class="form-select" :class="{ 'is-invalid': form.errors.patient_id }" id="patient_id" v-model="form.patient_id">
                                        <option value="" disabled>Choose a patient...</option>
                                        <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                                            {{ patient.last_name }}, {{ patient.first_name }}
                                        </option>
                                    </select>
                                    <div class="invalid-feedback" v-if="form.errors.patient_id">{{ form.errors.patient_id }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="doctor_id" class="form-label">Select Doctor</label>
                                    <select class="form-select" :class="{ 'is-invalid': form.errors.doctor_id }" id="doctor_id" v-model="form.doctor_id">
                                        <option value="" disabled>Choose a doctor...</option>
                                        <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                                            Dr. {{ doctor.last_name }} ({{ doctor.specialization }})
                                        </option>
                                    </select>
                                    <div class="invalid-feedback" v-if="form.errors.doctor_id">{{ form.errors.doctor_id }}</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="appointment_date" class="form-label">Date</label>
                                    <input type="date" class="form-control" :class="{ 'is-invalid': form.errors.appointment_date }" id="appointment_date" v-model="form.appointment_date">
                                    <div class="invalid-feedback" v-if="form.errors.appointment_date">{{ form.errors.appointment_date }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="appointment_time" class="form-label">Time</label>
                                    <input type="time" class="form-control" :class="{ 'is-invalid': form.errors.appointment_time }" id="appointment_time" v-model="form.appointment_time">
                                    <div class="invalid-feedback" v-if="form.errors.appointment_time">{{ form.errors.appointment_time }}</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="notes" class="form-label">Reason for Visit / Notes</label>
                                <textarea class="form-control" :class="{ 'is-invalid': form.errors.notes }" id="notes" rows="3" v-model="form.notes"></textarea>
                                <div class="invalid-feedback" v-if="form.errors.notes">{{ form.errors.notes }}</div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <Link :href="route('appointments.index')" class="btn btn-outline-secondary">Cancel</Link>
                                <button type="button" class="btn btn-outline-secondary" @click="form.reset()">Clear form</button>
                                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    Book Appointment
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