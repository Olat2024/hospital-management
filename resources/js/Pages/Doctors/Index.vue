<!-- resources/js/Pages/Doctors/Index.vue -->
<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmAction } from '@/Composables/useSweetAlert';

const props = defineProps({
    doctors: Object,
    filters: Object
});

const search = ref(props.filters.search);

const applyFilters = (page = 1) => {
    router.get(route('doctors.index'), {
        search: search.value || undefined,
        page: page > 1 ? page : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const deactivate = async (doctor) => {
    const confirmed = await confirmAction({
        title: `Deactivate Dr. ${doctor.first_name} ${doctor.last_name}?`,
        text: 'They will no longer accept new appointments, and existing records will be kept.',
        confirmButtonText: 'Deactivate doctor',
    });

    if (confirmed) {
        router.delete(route('doctors.destroy', doctor.id), { preserveScroll: true });
    }
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
        <div class="app-page-header">
            <div class="app-page-header-content">
                <div class="app-page-eyebrow">Clinical team</div>
                <h1 class="app-page-title">Doctors</h1>
                <p class="app-page-description">{{ doctors.total }} staff member<span v-if="doctors.total !== 1">s</span></p>
            </div>
            <div class="app-page-header-actions">
                <Link :href="route('doctors.create')" class="btn btn-primary">
                    <i class="bi bi-person-badge me-1"></i> Add Doctor
                </Link>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body py-3">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters()">
                    <div class="col-md-6">
                        <label for="search" class="form-label small text-muted">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input
                                id="search"
                                type="text"
                                class="form-control"
                                placeholder="Name, specialization or email..."
                                v-model="search"
                            >
                        </div>
                    </div>
                    <div class="col-md-6 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <button type="button" class="btn btn-outline-secondary" @click="search = ''; applyFilters()">Clear</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Doctor</th>
                            <th>Specialization</th>
                            <th>Login Email</th>
                            <th>Phone</th>
                            <th>Appointments</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="doctor in doctors.data" :key="doctor.id">
                            <td class="fw-semibold">Dr. {{ doctor.first_name }} {{ doctor.last_name }}</td>
                            <td>{{ doctor.specialization }}</td>
                            <td class="text-muted">{{ doctor.user?.email || '—' }}</td>
                            <td>{{ doctor.phone }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ doctor.appointments_count }}</span>
                            </td>
                            <td>
                                <span class="badge" :class="doctor.is_active ? 'bg-success' : 'bg-secondary'">
                                    {{ doctor.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <Link :href="route('doctors.edit', doctor.id)" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </Link>
                                    <button
                                        v-if="doctor.is_active"
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        @click="deactivate(doctor)"
                                    >
                                        <i class="bi bi-person-slash"></i> Deactivate
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="doctors.data.length === 0">
                            <td colspan="7" class="text-center py-5 text-muted">No doctors found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3" v-if="doctors.last_page > 1">
                <span class="small text-muted">
                    Showing {{ doctors.from }}&ndash;{{ doctors.to }} of {{ doctors.total }}
                </span>
                <div class="btn-group">
                    <button
                        v-for="(link, i) in doctors.links"
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
    </AppLayout>
</template>
