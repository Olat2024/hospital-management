<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    admissions: Object,
    filters: { type: Object, default: () => ({ search: '', status: 'admitted' }) },
    summary: { type: Object, default: () => ({ admitted: 0, dischargedToday: 0 }) },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'admitted');

const applyFilters = () => router.get(route('admissions.index'), {
    search: search.value || undefined,
    status: status.value !== 'admitted' ? status.value : undefined,
}, { preserveState: true, replace: true });

const formatDate = (value) => value
    ? new Date(value).toLocaleString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
    : '—';
</script>

<template>
    <AppLayout>
        <div class="clinical-page">
            <div class="app-page-header">
                <div class="app-page-header-content">
                    <div class="app-page-eyebrow">Inpatient care</div>
                    <h1 class="app-page-title">Admissions</h1>
                    <p class="app-page-description">Monitor active inpatients, admission reasons, and recent discharges.</p>
                </div>
                <form class="app-page-header-actions" @submit.prevent="applyFilters">
                    <label for="admissions-search" class="visually-hidden">Search admissions</label>
                    <input id="admissions-search" v-model="search" class="form-control" placeholder="Search patient, MRN, or reason">
                    <select v-model="status" class="form-select" style="width: auto;" aria-label="Filter admission status" @change="applyFilters">
                        <option value="admitted">Currently admitted</option>
                        <option value="discharged">Discharged</option>
                        <option value="all">All records</option>
                    </select>
                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search" /></button>
                </form>
            </div>

            <section class="row g-3 mb-4" aria-label="Admission summary">
                <div class="col-sm-6 col-xl-4">
                    <div class="kpi-tile kpi-tile-blue h-100">
                        <span class="kpi-tile-icon"><i class="bi bi-hospital" /></span>
                        <div>
                            <div class="kpi-tile-label">Active inpatients</div>
                            <div class="kpi-tile-value">{{ summary.admitted }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-4">
                    <div class="kpi-tile kpi-tile-green h-100">
                        <span class="kpi-tile-icon"><i class="bi bi-box-arrow-right" /></span>
                        <div>
                            <div class="kpi-tile-label">Discharged today</div>
                            <div class="kpi-tile-value">{{ summary.dischargedToday }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-xl-4">
                    <div class="kpi-tile kpi-tile-teal h-100">
                        <span class="kpi-tile-icon"><i class="bi bi-list-ul" /></span>
                        <div>
                            <div class="kpi-tile-label">Matching records</div>
                            <div class="kpi-tile-value">{{ admissions.total }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="card clinical-panel">
                <div class="card-header">
                    <h2>Inpatient register</h2>
                    <span class="small text-muted">{{ admissions.total }} record{{ admissions.total === 1 ? '' : 's' }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Status</th>
                                <th>Admitted</th>
                                <th>Reason &amp; notes</th>
                                <th>Contact</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="admission in admissions.data" :key="admission.id">
                                <td>
                                    <div class="fw-semibold">{{ admission.patient.first_name }} {{ admission.patient.last_name }}</div>
                                    <div class="small text-muted"><code>{{ admission.patient.mrn }}</code></div>
                                </td>
                                <td>
                                    <span class="badge text-capitalize" :class="admission.status === 'admitted' ? 'bg-primary' : 'bg-success'">
                                        {{ admission.status }}
                                    </span>
                                    <div v-if="admission.discharged_at" class="small text-muted mt-1">
                                        {{ formatDate(admission.discharged_at) }}
                                    </div>
                                </td>
                                <td>
                                    <div>{{ formatDate(admission.admitted_at) }}</div>
                                    <div v-if="admission.admitted_by" class="small text-muted">By {{ admission.admitted_by.name }}</div>
                                </td>
                                <td>
                                    <div>{{ admission.reason }}</div>
                                    <div v-if="admission.discharge_notes" class="small text-muted mt-1">{{ admission.discharge_notes }}</div>
                                </td>
                                <td>{{ admission.patient.phone || '—' }}</td>
                                <td class="text-end">
                                    <Link :href="route('patients.show', admission.patient.id)" class="btn btn-sm btn-outline-primary">
                                        Open chart
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="admissions.data.length === 0">
                                <td colspan="6" class="text-center py-5 text-muted">No admissions match your filter.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="admissions.last_page > 1" class="card-footer bg-white">
                    <Pagination :paginator="admissions" aria-label="Admissions pagination" show-summary />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
