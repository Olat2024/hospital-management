<!-- resources/js/Pages/Patients/Index.vue -->
<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmAction } from '@/Composables/useSweetAlert';

const props = defineProps({
    patients: Object,
    filters: Object,
    can: Object
});

const search = ref(props.filters.search);
const gender = ref(props.filters.gender);
const includeArchived = ref(props.filters.include_archived || false);

const hasLinkedHistory = (patient) => patient.appointments_exists
    || patient.medical_records_exists
    || patient.vital_signs_exists
    || patient.invoices_exists
    || patient.admissions_exists
    || patient.referrals_exists
    || patient.antenatal_cards_exists;

const applyFilters = (page = 1) => {
    router.get(route('patients.index'), {
        search: search.value,
        gender: gender.value,
        include_archived: includeArchived.value ? 1 : undefined,
        page: page > 1 ? page : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const archivePatient = async (patient) => {
    const hasHistory = hasLinkedHistory(patient);

    const confirmed = await confirmAction({
        title: `${hasHistory ? 'Archive' : 'Permanently delete'} ${patient.first_name} ${patient.last_name}?`,
        text: hasHistory
            ? 'This patient has linked records. The patient will be archived, and their clinical, ANC, and billing history will be preserved.'
            : 'This patient has no appointments, clinical notes, vitals, admissions, referrals, ANC cards, or invoices. Their record will be permanently deleted and cannot be restored.',
        confirmButtonText: hasHistory ? 'Archive patient' : 'Permanently delete',
    });

    if (confirmed) {
        router.delete(route('patients.destroy', patient.id), { preserveScroll: true });
    }
};

const restorePatient = async (patient) => {
    const confirmed = await confirmAction({
        title: `Restore ${patient.first_name} ${patient.last_name}?`,
        text: 'The patient will return to active patient lists.',
        confirmButtonText: 'Restore patient',
        icon: 'question',
    });

    if (confirmed) {
        router.post(route('patients.restore', patient.id), {}, { preserveScroll: true });
    }
};

const age = (dob) => {
    if (!dob) return null;
    const diff = Date.now() - new Date(dob).getTime();
    return Math.floor(diff / (365.25 * 24 * 60 * 60 * 1000));
};

// Laravel links may be absolute or relative depending on the request host.
const pageFrom = (url) => {
    if (!url) return 1;
    try {
        return Number(new URL(url, window.location.origin).searchParams.get('page')) || 1;
    } catch {
        return 1;
    }
};
</script>

<template>
    <AppLayout>
        <div class="clinical-page patients-page">
        <div class="clinical-page-heading">
            <div>
                <div class="clinical-eyebrow">Patient directory</div>
                <h1 class="clinical-page-title">Patients</h1>
                <p class="clinical-page-subtitle">{{ patients.total }} registered patient<span v-if="patients.total !== 1">s</span></p>
            </div>
            <Link v-if="can.create" :href="route('patients.create')" class="btn btn-primary px-3 py-2">
                <i class="bi bi-person-plus me-1"></i> Register Patient
            </Link>
        </div>

        <div class="card clinical-panel mb-4">
            <div class="card-body py-3">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters()">
                    <div class="col-md-5">
                        <label for="search" class="form-label">Search patients</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input
                                id="search"
                                type="text"
                                class="form-control"
                                placeholder="Name, MRN, phone or email..."
                                v-model="search"
                            >
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select id="gender" class="form-select" v-model="gender">
                            <option value="">All</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            @click="search = ''; gender = ''; applyFilters()"
                        >
                            Clear
                        </button>
                    </div>
                    <div v-if="can.manage" class="col-12">
                        <div class="form-check">
                            <input id="include-archived" v-model="includeArchived" type="checkbox" class="form-check-input" @change="applyFilters()">
                            <label for="include-archived" class="form-check-label">Include archived patients</label>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card clinical-panel">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>MRN</th>
                            <th>Patient</th>
                            <th>Age / Gender</th>
                            <th>Phone</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="patient in patients.data" :key="patient.id">
                            <td><code>{{ patient.mrn }}</code></td>
                            <td>
                                <Link v-if="!patient.deleted_at" :href="route('patients.show', patient.id)" class="text-decoration-none fw-semibold">
                                    {{ patient.last_name }}, {{ patient.first_name }}
                                </Link>
                                <span v-else class="fw-semibold">{{ patient.last_name }}, {{ patient.first_name }}</span>
                                <div class="small text-muted" v-if="patient.email">{{ patient.email }}</div>
                                <span v-if="patient.deleted_at" class="badge bg-secondary mt-1">Archived</span>
                            </td>
                            <td>
                                <template v-if="age(patient.dob) !== null">
                                    {{ age(patient.dob) }} yrs
                                    <span v-if="patient.gender" class="text-muted">&middot; {{ patient.gender }}</span>
                                </template>
                                <span v-else class="text-muted">&mdash;</span>
                            </td>
                            <td>{{ patient.phone || '—' }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                    <Link v-if="!patient.deleted_at" :href="route('patients.show', patient.id)" class="btn btn-sm btn-outline-primary">
                                        View Chart <i class="bi bi-arrow-right"></i>
                                    </Link>
                                    <template v-if="can.manage">
                                        <Link v-if="!patient.deleted_at" :href="route('patients.edit', patient.id)" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </Link>
                                        <button v-if="!patient.deleted_at" type="button" class="btn btn-sm btn-outline-danger" @click="archivePatient(patient)">
                                            <i class="bi" :class="hasLinkedHistory(patient) ? 'bi-archive' : 'bi-trash'"></i>
                                            {{ hasLinkedHistory(patient) ? 'Archive' : 'Delete permanently' }}
                                        </button>
                                        <button v-else type="button" class="btn btn-sm btn-outline-success" @click="restorePatient(patient)">
                                            <i class="bi bi-arrow-counterclockwise"></i> Restore
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="patients.data.length === 0">
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-person-search d-block fs-3 mb-2"></i>
                                No patients match your search.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3" v-if="patients.last_page > 1">
                <span class="small text-muted">
                    Showing {{ patients.from }}&ndash;{{ patients.to }} of {{ patients.total }}
                </span>
                <div class="btn-group">
                    <button
                        v-for="(link, i) in patients.links"
                        :key="i"
                        type="button"
                        class="btn btn-sm"
                        :class="link.active ? 'btn-primary' : 'btn-outline-secondary'"
                        :disabled="!link.url"
                        @click="applyFilters(pageFrom(link.url))"
                        v-html="link.label"
                    ></button>
                </div>
            </div>
        </div>
        </div>
    </AppLayout>
</template>
