<!-- resources/js/Pages/Patients/Show.vue -->
<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    patient: Object,
    can: Object
});

const age = (dob) => {
    if (!dob) return null;
    return Math.floor((Date.now() - new Date(dob).getTime()) / (365.25 * 24 * 60 * 60 * 1000));
};

const statusBadge = {
    scheduled: 'bg-primary',
    completed: 'bg-success',
    cancelled: 'bg-secondary',
    no_show: 'bg-danger',
};

const formatDate = (value) =>
    new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });

// ---- Add clinical note ----
const showNoteForm = ref(false);

const noteForm = useForm({
    patient_id: props.patient.id,
    diagnosis: '',
    prescription: '',
    notes: '',
});

const submitNote = () => {
    noteForm.post(route('medical-records.store'), {
        preserveScroll: true,
        onSuccess: () => {
            noteForm.reset();
            showNoteForm.value = false;
        },
    });
};
</script>

<template>
    <AppLayout>
        <div class="clinical-page">
        <header class="clinical-page-heading">
            <div>
                <div class="clinical-eyebrow">Patient record</div>
                <h1 class="clinical-page-title">{{ patient.first_name }} {{ patient.last_name }}</h1>
                <p class="clinical-page-subtitle">Medical record number <code>{{ patient.mrn }}</code></p>
            </div>
            <Link :href="route('patients.index')" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Patient directory
            </Link>
        </header>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card clinical-panel patient-profile-card">
                    <div class="card-body">
                        <div class="patient-profile-icon"><i class="bi bi-person"></i></div>
                        <h2 class="h5 card-title mt-3">{{ patient.first_name }} {{ patient.last_name }}</h2>
                        <p class="text-muted mb-1">MRN: <code>{{ patient.mrn }}</code></p>
                        <p class="text-muted mb-3">
                            <template v-if="age(patient.dob) !== null">{{ age(patient.dob) }} yrs</template>
                            <span v-if="patient.gender"> &middot; {{ patient.gender }}</span>
                            <span v-if="patient.dob_display"> &middot; Born {{ patient.dob_display }}</span>
                        </p>

                        <h3 class="patient-section-title mt-4">Contact information</h3>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><strong>Phone:</strong> {{ patient.phone }}</li>
                            <li class="mb-2" v-if="patient.email"><strong>Email:</strong> {{ patient.email }}</li>
                            <li class="mb-2"><strong>Address:</strong> {{ patient.address }}</li>
                            <li v-if="patient.blood_group"><strong>Blood Group:</strong> {{ patient.blood_group }}</li>
                        </ul>

                        <h3 class="patient-section-title mt-4" v-if="patient.allergies">Allergies</h3>
                        <p class="allergy-notice mb-0" v-if="patient.allergies">
                            <i class="bi bi-exclamation-triangle me-1"></i>{{ patient.allergies }}
                        </p>

                        <h3 class="patient-section-title mt-4" v-if="patient.emergency_contact_name">Emergency contact</h3>
                        <p class="mb-0" v-if="patient.emergency_contact_name">
                            {{ patient.emergency_contact_name }}
                            <span v-if="patient.emergency_contact_phone"> &middot; {{ patient.emergency_contact_phone }}</span>
                        </p>

                        <h3 class="patient-section-title mt-4" v-if="patient.insurance_provider || patient.insurance_number">Insurance</h3>
                        <p class="mb-0" v-if="patient.insurance_provider || patient.insurance_number">
                            <span v-if="patient.insurance_provider">{{ patient.insurance_provider }}</span>
                            <span v-if="patient.insurance_number"> &middot; <code>{{ patient.insurance_number }}</code></span>
                        </p>

                        <div class="d-grid gap-2 mt-4" v-if="can.bookAppointment">
                            <Link :href="route('appointments.create')" class="btn btn-primary btn-sm">
                                <i class="bi bi-calendar-plus me-1"></i> Schedule Appointment
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div v-if="can.viewVitals" class="card clinical-panel mb-4">
                    <div class="card-header">
                        <h2>
                            Vital signs
                            <span class="badge bg-light text-dark border ms-2">{{ (patient.vital_signs || []).length }}</span>
                        </h2>
                        <Link
                            v-if="can.manageVitals"
                            :href="route('nursing.index', { patient_id: patient.id })"
                            class="btn btn-sm btn-primary"
                        >
                            <i class="bi bi-heart-pulse me-1"></i> Record vitals
                        </Link>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Recorded</th>
                                    <th>Blood pressure</th>
                                    <th>Pulse</th>
                                    <th>Respiration</th>
                                    <th>Temperature</th>
                                    <th>Oxygen</th>
                                    <th>Weight</th>
                                    <th>Height</th>
                                    <th>Pain</th>
                                    <th>Notes</th>
                                    <th>Recorded by</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="vital in patient.vital_signs" :key="vital.id">
                                    <td>{{ new Date(vital.recorded_at).toLocaleString() }}</td>
                                    <td>
                                        <template v-if="vital.systolic_blood_pressure || vital.diastolic_blood_pressure">
                                            {{ vital.systolic_blood_pressure || '—' }}/{{ vital.diastolic_blood_pressure || '—' }} mmHg
                                        </template>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                    <td>{{ vital.pulse_rate ? `${vital.pulse_rate} bpm` : '—' }}</td>
                                    <td>{{ vital.respiratory_rate ? `${vital.respiratory_rate}/min` : '—' }}</td>
                                    <td>{{ vital.temperature_celsius ? `${vital.temperature_celsius} °C` : '—' }}</td>
                                    <td>{{ vital.oxygen_saturation ? `${vital.oxygen_saturation}%` : '—' }}</td>
                                    <td>{{ vital.weight_kg ? `${vital.weight_kg} kg` : '—' }}</td>
                                    <td>{{ vital.height_cm ? `${vital.height_cm} cm` : '—' }}</td>
                                    <td>{{ vital.pain_score ?? '—' }}</td>
                                    <td>{{ vital.notes || '—' }}</td>
                                    <td>{{ vital.recorder?.name || 'Former staff member' }}</td>
                                </tr>
                                <tr v-if="!patient.vital_signs?.length">
                                    <td colspan="11" class="text-center py-4 text-muted">No vital signs have been recorded for this patient.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="can.viewAppointments" class="card clinical-panel mb-4">
                    <div class="card-header">
                        <h2>Appointment history</h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Doctor</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="appointment in patient.appointments" :key="appointment.id">
                                        <td><strong>{{ appointment.scheduled_date }}</strong></td>
                                        <td>{{ appointment.scheduled_time }}</td>
                                        <td>
                                            <template v-if="appointment.doctor">
                                                Dr. {{ appointment.doctor.first_name }} {{ appointment.doctor.last_name }}
                                            </template>
                                            <span v-else class="text-muted">Unassigned</span>
                                        </td>
                                        <td class="text-truncate" style="max-width: 180px;" :title="appointment.reason">
                                            {{ appointment.reason || '—' }}
                                        </td>
                                        <td>
                                            <span class="badge" :class="statusBadge[appointment.status] || 'bg-secondary'">
                                                {{ appointment.status }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="!patient.appointments || patient.appointments.length === 0">
                                        <td colspan="5" class="text-center py-4 text-muted">No appointment history found for this patient.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div v-if="can.viewMedicalRecords" class="card clinical-panel">
                    <div class="card-header">
                        <h2>
                            Clinical Notes
                            <span class="badge bg-light text-dark border ms-2">{{ (patient.medical_records || []).length }}</span>
                        </h2>
                        <button v-if="can.addNote" class="btn btn-sm btn-success" @click="showNoteForm = !showNoteForm">
                            <i class="bi bi-journal-plus me-1"></i> Add Clinical Note
                        </button>
                    </div>

                    <!-- Inline note form -->
                    <div class="card-body border-bottom" v-if="showNoteForm && can.addNote">
                        <form @submit.prevent="submitNote">
                            <div class="mb-3">
                                <label for="diagnosis" class="form-label">Diagnosis <span class="text-danger">*</span></label>
                                <textarea
                                    id="diagnosis"
                                    class="form-control"
                                    :class="{ 'is-invalid': noteForm.errors.diagnosis }"
                                    rows="2"
                                    v-model="noteForm.diagnosis"
                                    required
                                ></textarea>
                                <div class="invalid-feedback" v-if="noteForm.errors.diagnosis">{{ noteForm.errors.diagnosis }}</div>
                            </div>
                            <div class="mb-3">
                                <label for="prescription" class="form-label">Prescription</label>
                                <textarea
                                    id="prescription"
                                    class="form-control"
                                    :class="{ 'is-invalid': noteForm.errors.prescription }"
                                    rows="2"
                                    v-model="noteForm.prescription"
                                ></textarea>
                                <div class="invalid-feedback" v-if="noteForm.errors.prescription">{{ noteForm.errors.prescription }}</div>
                            </div>
                            <div class="mb-3">
                                <label for="note" class="form-label">Notes</label>
                                <textarea
                                    id="note"
                                    class="form-control"
                                    :class="{ 'is-invalid': noteForm.errors.notes }"
                                    rows="2"
                                    v-model="noteForm.notes"
                                ></textarea>
                                <div class="invalid-feedback" v-if="noteForm.errors.notes">{{ noteForm.errors.notes }}</div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="showNoteForm = false">Cancel</button>
                                <button type="submit" class="btn btn-sm btn-success" :disabled="noteForm.processing">
                                    <span v-if="noteForm.processing" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    Save Note
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="list-group list-group-flush">
                        <div class="list-group-item py-3" v-for="record in patient.medical_records" :key="record.id">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-0">{{ record.diagnosis }}</h6>
                                <span class="small text-muted">{{ formatDate(record.created_at) }}</span>
                            </div>
                            <div class="small text-muted mb-2">
                                By
                                <template v-if="record.doctor">
                                    Dr. {{ record.doctor.first_name }} {{ record.doctor.last_name }}
                                </template>
                                <template v-else>Unknown practitioner</template>
                            </div>
                            <p class="mb-1" v-if="record.prescription"><strong>Prescription:</strong> {{ record.prescription }}</p>
                            <p class="mb-0" v-if="record.notes"><strong>Notes:</strong> {{ record.notes }}</p>
                        </div>
                        <div class="list-group-item py-4 text-center text-muted" v-if="!(patient.medical_records || []).length">
                            No clinical notes recorded yet.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.patient-profile-icon {
    align-items: center;
    background: linear-gradient(145deg, #e8f4fa, #e5f7f2);
    border-radius: 0.9rem;
    color: #176b87;
    display: flex;
    font-size: 1.7rem;
    height: 54px;
    justify-content: center;
    width: 54px;
}

.patient-section-title {
    border-bottom: 1px solid #e8edf3;
    color: #61758b;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    padding-bottom: 0.55rem;
    text-transform: uppercase;
}

.allergy-notice {
    background: #fff3f1;
    border-radius: 0.55rem;
    color: #b34035;
    padding: 0.7rem;
}
</style>
