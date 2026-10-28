<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    patients: Object,
    filters: Object,
    summary: Object,
    selectedPatient: Object,
    canManage: Boolean,
});

const search = ref(props.filters.search || '');
const selectedPatient = ref(null);
const showVitalsForm = ref(false);

const form = useForm({
    patient_id: null,
    systolic_blood_pressure: '',
    diastolic_blood_pressure: '',
    pulse_rate: '',
    respiratory_rate: '',
    temperature_celsius: '',
    oxygen_saturation: '',
    weight_kg: '',
    height_cm: '',
    pain_score: '',
    notes: '',
});

const summaryCards = [
    {
        label: 'Registered patients',
        value: props.summary.patientCount,
        icon: 'bi-people',
        tone: 'nursing-card-blue',
        // detail: 'In this hospital',
    },
    {
        label: 'Readings today',
        value: props.summary.readingsToday,
        icon: 'bi-activity',
        tone: 'nursing-card-teal',
        detail: 'Vitals recorded today',
    },
    {
        label: 'Patients measured',
        value: props.summary.patientsMeasuredToday,
        icon: 'bi-person-check',
        tone: 'nursing-card-violet',
        detail: 'Unique patients today',
    },
];

const formatDate = (value) => value
    ? new Date(value).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    })
    : 'No readings yet';

const bloodPressure = (vital) => {
    if (!vital?.systolic_blood_pressure && !vital?.diastolic_blood_pressure) return '—';
    return `${vital.systolic_blood_pressure || '—'}/${vital.diastolic_blood_pressure || '—'} mmHg`;
};

const applySearch = () => router.get(route('nursing.index'), {
    search: search.value || undefined,
}, { preserveState: true, preserveScroll: true, replace: true });

const openVitalsForm = (patient) => {
    form.reset();
    form.patient_id = patient.id;
    selectedPatient.value = patient;
    showVitalsForm.value = true;
};

const closeVitalsForm = () => {
    showVitalsForm.value = false;
    selectedPatient.value = null;
    form.clearErrors();
};

const submitVitals = () => {
    form.post(route('nursing.vitals.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showVitalsForm.value = false;
            selectedPatient.value = null;
        },
    });
};

watch(
    () => props.selectedPatient,
    (patient) => {
        if (patient) openVitalsForm(patient);
    },
    { immediate: true },
);
</script>

<template>
    <AppLayout>
        <div class="clinical-page nursing-page">
            <header class="app-page-header">
                <div class="app-page-header-content">
                    <div class="app-page-eyebrow">Patient care</div>
                    <h1 class="app-page-title">Nursing station</h1>
                    <p class="app-page-description">Record and review patient vital signs in one place.</p>
                </div>
            </header>

            <section class="row g-3 mb-4" aria-label="Nursing summary">
                <div v-for="card in summaryCards" :key="card.label" class="col-sm-6 col-xl-4">
                    <article class="nursing-stat-card h-100" :class="card.tone">
                        <div class="nursing-stat-top">
                            <span class="nursing-stat-icon"><i class="bi" :class="card.icon"></i></span>
                            <span class="nursing-stat-context">{{ card.detail }}</span>
                        </div>
                        <div class="nursing-stat-label">{{ card.label }}</div>
                        <div class="nursing-stat-value">{{ card.value }}</div>
                    </article>
                </div>
            </section>

            <section class="card clinical-panel">
                <div class="card-header">
                    <div>
                        <h2>Patient observations</h2>
                        <p class="small text-muted mb-0">Search patients and review their latest recorded readings.</p>
                    </div>
                    <form class="nursing-search" @submit.prevent="applySearch">
                        <label for="nursing-search" class="visually-hidden">Search patients</label>
                        <input
                            id="nursing-search"
                            v-model="search"
                            class="form-control"
                            placeholder="Search name, MRN, or phone"
                        >
                        <button class="btn btn-outline-primary" type="submit" aria-label="Search">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Latest blood pressure</th>
                                <th>Pulse</th>
                                <th>Temperature</th>
                                <th>Oxygen</th>
                                <th>Last recorded</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="patient in patients.data" :key="patient.id">
                                <td>
                                    <Link :href="route('patients.show', patient.id)" class="fw-semibold text-decoration-none">
                                        {{ patient.first_name }} {{ patient.last_name }}
                                    </Link>
                                    <div class="small text-muted">{{ patient.mrn }}</div>
                                </td>
                                <td>{{ bloodPressure(patient.latest_vital_sign) }}</td>
                                <td>{{ patient.latest_vital_sign?.pulse_rate ? `${patient.latest_vital_sign.pulse_rate} bpm` : '—' }}</td>
                                <td>{{ patient.latest_vital_sign?.temperature_celsius ? `${patient.latest_vital_sign.temperature_celsius} °C` : '—' }}</td>
                                <td>{{ patient.latest_vital_sign?.oxygen_saturation ? `${patient.latest_vital_sign.oxygen_saturation}%` : '—' }}</td>
                                <td>
                                    <span>{{ formatDate(patient.latest_vital_sign?.recorded_at) }}</span>
                                    <div v-if="patient.latest_vital_sign?.recorder" class="small text-muted">
                                        By {{ patient.latest_vital_sign.recorder.name }}
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button
                                        v-if="canManage"
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        @click="openVitalsForm(patient)"
                                    >
                                        <i class="bi bi-heart-pulse me-1"></i> Record
                                    </button>
                                    <Link
                                        v-else
                                        :href="route('patients.show', patient.id)"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        View chart
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="patients.data.length === 0">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    No patients found. Try a different name, medical record number, or phone.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="patients.last_page > 1" class="card-footer bg-white d-flex justify-content-between align-items-center">
                    <span class="small text-muted">Showing {{ patients.from }}–{{ patients.to }} of {{ patients.total }}</span>
                    <div class="d-flex gap-2">
                        <Link v-if="patients.prev_page_url" :href="patients.prev_page_url" class="btn btn-sm btn-outline-secondary">Previous</Link>
                        <Link v-if="patients.next_page_url" :href="patients.next_page_url" class="btn btn-sm btn-outline-secondary">Next</Link>
                    </div>
                </div>
            </section>

            <div v-if="showVitalsForm && selectedPatient" class="nursing-modal-backdrop" @click.self="closeVitalsForm">
                <section class="nursing-modal card" role="dialog" aria-modal="true" aria-labelledby="vitals-form-title">
                    <div class="card-header d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <div class="app-page-eyebrow">New observation</div>
                            <h2 id="vitals-form-title" class="h5 mb-1">Record patient vitals</h2>
                            <p class="small text-muted mb-0">
                                {{ selectedPatient.first_name }} {{ selectedPatient.last_name }} · {{ selectedPatient.mrn }}
                            </p>
                        </div>
                        <button type="button" class="btn-close" aria-label="Close" @click="closeVitalsForm"></button>
                    </div>
                    <form @submit.prevent="submitVitals">
                        <div class="card-body">
                            <p class="small text-muted">Enter the measurements taken. At least one clinical measurement is required.</p>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label for="vitals-systolic" class="form-label">Systolic BP <span class="text-muted">(mmHg)</span></label>
                                    <input id="vitals-systolic" v-model="form.systolic_blood_pressure" type="number" min="40" max="300" class="form-control" :class="{ 'is-invalid': form.errors.systolic_blood_pressure }">
                                    <div class="invalid-feedback">{{ form.errors.systolic_blood_pressure }}</div>
                                </div>
                                <div class="col-6">
                                    <label for="vitals-diastolic" class="form-label">Diastolic BP <span class="text-muted">(mmHg)</span></label>
                                    <input id="vitals-diastolic" v-model="form.diastolic_blood_pressure" type="number" min="20" max="200" class="form-control" :class="{ 'is-invalid': form.errors.diastolic_blood_pressure }">
                                    <div class="invalid-feedback">{{ form.errors.diastolic_blood_pressure }}</div>
                                </div>
                                <div class="col-6">
                                    <label for="vitals-pulse" class="form-label">Pulse <span class="text-muted">(bpm)</span></label>
                                    <input id="vitals-pulse" v-model="form.pulse_rate" type="number" min="20" max="300" class="form-control" :class="{ 'is-invalid': form.errors.pulse_rate }">
                                    <div class="invalid-feedback">{{ form.errors.pulse_rate }}</div>
                                </div>
                                <div class="col-6">
                                    <label for="vitals-respiration" class="form-label">Respiratory rate <span class="text-muted">(per min)</span></label>
                                    <input id="vitals-respiration" v-model="form.respiratory_rate" type="number" min="4" max="80" class="form-control" :class="{ 'is-invalid': form.errors.respiratory_rate }">
                                    <div class="invalid-feedback">{{ form.errors.respiratory_rate }}</div>
                                </div>
                                <div class="col-6">
                                    <label for="vitals-temperature" class="form-label">Temperature <span class="text-muted">(°C)</span></label>
                                    <input id="vitals-temperature" v-model="form.temperature_celsius" type="number" min="25" max="45" step="0.1" class="form-control" :class="{ 'is-invalid': form.errors.temperature_celsius }">
                                    <div class="invalid-feedback">{{ form.errors.temperature_celsius }}</div>
                                </div>
                                <div class="col-6">
                                    <label for="vitals-oxygen" class="form-label">Oxygen saturation <span class="text-muted">(%)</span></label>
                                    <input id="vitals-oxygen" v-model="form.oxygen_saturation" type="number" min="50" max="100" class="form-control" :class="{ 'is-invalid': form.errors.oxygen_saturation }">
                                    <div class="invalid-feedback">{{ form.errors.oxygen_saturation }}</div>
                                </div>
                                <div class="col-6">
                                    <label for="vitals-weight" class="form-label">Weight <span class="text-muted">(kg)</span></label>
                                    <input id="vitals-weight" v-model="form.weight_kg" type="number" min="0.2" max="500" step="0.01" class="form-control" :class="{ 'is-invalid': form.errors.weight_kg }">
                                    <div class="invalid-feedback">{{ form.errors.weight_kg }}</div>
                                </div>
                                <div class="col-6">
                                    <label for="vitals-height" class="form-label">Height <span class="text-muted">(cm)</span></label>
                                    <input id="vitals-height" v-model="form.height_cm" type="number" min="20" max="250" step="0.01" class="form-control" :class="{ 'is-invalid': form.errors.height_cm }">
                                    <div class="invalid-feedback">{{ form.errors.height_cm }}</div>
                                </div>
                                <div class="col-6">
                                    <label for="vitals-pain" class="form-label">Pain score <span class="text-muted">(0–10)</span></label>
                                    <input id="vitals-pain" v-model="form.pain_score" type="number" min="0" max="10" class="form-control" :class="{ 'is-invalid': form.errors.pain_score }">
                                    <div class="invalid-feedback">{{ form.errors.pain_score }}</div>
                                </div>
                                <div class="col-12">
                                    <label for="vitals-notes" class="form-label">Observation notes</label>
                                    <textarea id="vitals-notes" v-model="form.notes" rows="2" maxlength="2000" class="form-control" :class="{ 'is-invalid': form.errors.notes }"></textarea>
                                    <div class="invalid-feedback">{{ form.errors.notes }}</div>
                                </div>
                            </div>
                            <div v-if="form.errors.patient_id" class="text-danger small mt-3">{{ form.errors.patient_id }}</div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" @click="closeVitalsForm">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                Save readings
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.nursing-stat-card {
    align-items: center;
    background: #fff;
    border: 1px solid #e6edf3;
    border-left-width: 3px;
    border-radius: 0.75rem;
    box-shadow: 0 3px 12px rgb(34 56 84 / 4%);
    display: flex;
    gap: 0.8rem;
    min-height: 98px;
    padding: 0.85rem 0.95rem;
    position: relative;
}

.nursing-stat-top {
    align-items: center;
    display: flex;
}

.nursing-stat-icon {
    align-items: center;
    background: currentColor;
    border-radius: 0.65rem;
    display: inline-flex;
    flex: 0 0 auto;
    font-size: 0.95rem;
    height: 38px;
    justify-content: center;
    width: 38px;
}

.nursing-stat-icon i {
    filter: brightness(0) invert(1);
}

.nursing-stat-context,
.nursing-stat-label {
    color: #78879a;
    font-size: 0.68rem;
}

.nursing-stat-label {
    font-size: 0.72rem;
    font-weight: 600;
    line-height: 1.25;
    padding-top: 0.4rem;
}

.nursing-stat-context {
    background: #f3f6f9;
    border-radius: 1rem;
    line-height: 1;
    padding: 0.3rem 0.45rem;
    position: absolute;
    right: 0.7rem;
    top: 0.65rem;
}

.nursing-stat-value {
    color: #203047;
    font-size: 1.55rem;
    font-weight: 700;
    letter-spacing: -0.04em;
    line-height: 1.1;
    margin-top: 0.18rem;
}

.nursing-card-blue { border-left-color: #2878d0; color: #2878d0; }
.nursing-card-teal { border-left-color: #168b89; color: #168b89; }
.nursing-card-violet { border-left-color: #7855c4; color: #7855c4; }

.nursing-search {
    display: flex;
    gap: 0.5rem;
    max-width: 24rem;
    width: 100%;
}

.nursing-modal-backdrop {
    align-items: center;
    background: rgb(18 32 48 / 55%);
    display: flex;
    inset: 0;
    justify-content: center;
    overflow-y: auto;
    padding: 1rem;
    position: fixed;
    z-index: 1060;
}

.nursing-modal {
    max-height: calc(100vh - 2rem);
    max-width: 42rem;
    overflow-y: auto;
    width: 100%;
}

@media (max-width: 767.98px) {
    .nursing-search {
        max-width: none;
    }
}
</style>
