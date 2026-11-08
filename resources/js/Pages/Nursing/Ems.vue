<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    patients: Object,
    filters: Object,
    summary: Object,
});

const search = ref(props.filters.search || '');

const applySearch = () => router.get(route('nursing.ems'), {
    search: search.value || undefined,
}, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
});

const bloodPressure = (vital) => {
    if (!vital?.systolic_blood_pressure && !vital?.diastolic_blood_pressure) return '—';
    return `${vital.systolic_blood_pressure || '—'}/${vital.diastolic_blood_pressure || '—'} mmHg`;
};
</script>

<template>
    <AppLayout>
        <div class="clinical-page nursing-page">
            <header class="app-page-header">
                <div class="app-page-header-content">
                    <div class="app-page-eyebrow">Emergency support</div>
                    <h1 class="app-page-title">EMS flagged patients</h1>
                    <p class="app-page-description">Quick access to patients who need ambulance or emergency support coordination.</p>
                </div>
            </header>

            <section class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-4">
                    <article class="nursing-stat-card h-100 nursing-card-amber">
                        <div class="nursing-stat-top">
                            <span class="nursing-stat-icon"><i class="bi bi-ambulance"></i></span>
                            <span class="nursing-stat-context">Active cases</span>
                        </div>
                        <div class="nursing-stat-label">EMS flagged</div>
                        <div class="nursing-stat-value">{{ summary.total ?? 0 }}</div>
                    </article>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <article class="nursing-stat-card h-100 nursing-card-pink">
                        <div class="nursing-stat-top">
                            <span class="nursing-stat-icon"><i class="bi bi-gender-female"></i></span>
                            <span class="nursing-stat-context">Maternal support</span>
                        </div>
                        <div class="nursing-stat-label">Pregnant / maternal</div>
                        <div class="nursing-stat-value">{{ summary.pregnantPatients ?? 0 }}</div>
                    </article>
                </div>
            </section>

            <section class="card clinical-panel">
                <div class="card-header">
                    <div>
                        <h2>Emergency list</h2>
                        <p class="small text-muted mb-0">Patients currently flagged for ambulance or emergency support follow-up.</p>
                    </div>

                    <div class="d-flex gap-2 align-items-center">
                        <Link :href="route('nursing.index')" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>
                            Back to nursing
                        </Link>
                        <form class="nursing-search" @submit.prevent="applySearch">
                            <label for="ems-search" class="visually-hidden">Search EMS patients</label>
                            <input
                                id="ems-search"
                                v-model="search"
                                class="form-control"
                                placeholder="Search name, MRN, or phone"
                            >
                            <button class="btn btn-outline-primary" type="submit" aria-label="Search">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Maternal status</th>
                                <th>Latest blood pressure</th>
                                <th>Pulse</th>
                                <th>Oxygen</th>
                                <th>Last recorded</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="patient in patients.data" :key="patient.id">
                                <td>
                                    <Link :href="route('patients.show', patient.id)" class="fw-semibold text-decoration-none">
                                        {{ patient.first_name }} {{ patient.last_name }}
                                    </Link>
                                    <div class="small text-muted">{{ patient.mrn }}</div>
                                    <div class="mt-1">
                                        <span class="badge bg-warning text-dark">EMS</span>
                                    </div>
                                </td>
                                <td>
                                    <span v-if="patient.pregnancy_status" class="badge bg-pink-subtle text-danger">
                                        {{ patient.pregnancy_status }}
                                    </span>
                                    <span v-else class="text-muted small">Not specified</span>
                                </td>
                                <td>{{ bloodPressure(patient.latest_vital_sign) }}</td>
                                <td>{{ patient.latest_vital_sign?.pulse_rate ? `${patient.latest_vital_sign.pulse_rate} bpm` : '—' }}</td>
                                <td>{{ patient.latest_vital_sign?.oxygen_saturation ? `${patient.latest_vital_sign.oxygen_saturation}%` : '—' }}</td>
                                <td>{{ patient.latest_vital_sign?.recorded_at ? new Date(patient.latest_vital_sign.recorded_at).toLocaleString(undefined, {
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric',
                                    hour: 'numeric',
                                    minute: '2-digit',
                                }) : 'No readings yet' }}</td>
                            </tr>

                            <tr v-if="!patients.data.length">
                                <td colspan="6" class="text-center text-muted py-4">
                                    No EMS flagged patients were found for this hospital.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="patients.links && patients.links.length > 3" class="d-flex justify-content-center mt-3">
                    <nav aria-label="EMS pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li v-for="link in patients.links" :key="link.label" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                                <Link class="page-link" :href="link.url || '#!'" :preserve-scroll="true" :preserve-state="true" v-html="link.label"></Link>
                            </li>
                        </ul>
                    </nav>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
