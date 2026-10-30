<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    admissions: Object,
});

const formatDate = (value) =>
    new Date(value).toLocaleString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
</script>

<template>
    <AppLayout>
        <div class="app-page-header">
            <div class="app-page-header-content">
                <div class="app-page-eyebrow">Inpatient care</div>
                <h1 class="app-page-title">Admissions</h1>
                <p class="app-page-description">Patients currently admitted to the hospital.</p>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h2 class="h5 mb-0">Current inpatients</h2>
                <span class="small text-muted">{{ admissions.total }} patients</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>MRN</th>
                            <th>Admitted</th>
                            <th>Reason</th>
                            <th>Contact</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="admission in admissions.data" :key="admission.id">
                            <td>{{ admission.patient.first_name }} {{ admission.patient.last_name }}</td>
                            <td><code>{{ admission.patient.mrn }}</code></td>
                            <td>{{ formatDate(admission.admitted_at) }}</td>
                            <td>{{ admission.reason }}</td>
                            <td>{{ admission.patient.phone || '—' }}</td>
                            <td class="text-end">
                                <Link :href="route('patients.show', admission.patient.id)" class="btn btn-sm btn-outline-primary">Open patient record</Link>
                            </td>
                        </tr>
                        <tr v-if="admissions.data.length === 0">
                            <td colspan="6" class="text-center py-5 text-muted">There are no current inpatients.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="admissions.prev_page_url || admissions.next_page_url" class="card-footer bg-white d-flex justify-content-between">
                <Link v-if="admissions.prev_page_url" :href="admissions.prev_page_url" class="btn btn-sm btn-outline-secondary">Previous</Link>
                <span v-else />
                <Link v-if="admissions.next_page_url" :href="admissions.next_page_url" class="btn btn-sm btn-outline-secondary">Next</Link>
            </div>
        </div>
    </AppLayout>
</template>
