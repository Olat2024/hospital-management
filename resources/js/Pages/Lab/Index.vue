<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import PatientSearchSelect from '@/Components/PatientSearchSelect.vue';

const props = defineProps({
    orders: Object,
    patients: Array,
    filters: Object,
    summary: Object,
    canManage: Boolean,
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const activeOrderId = ref(null);

const orderForm = useForm({
    patient_id: '',
    test_name: '',
    category: '',
    priority: 'routine',
    notes: '',
});

const resultForm = useForm({
    status: 'completed',
    result_summary: '',
    result_notes: '',
});

const applyFilters = () => router.get(route('lab.index'), {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
}, { preserveState: true, replace: true });

const createOrder = () => orderForm.post(route('lab.store'), {
    preserveScroll: true,
    onSuccess: () => orderForm.reset('test_name', 'category', 'notes'),
});

const openResultEditor = (order) => {
    if (activeOrderId.value === order.id) {
        activeOrderId.value = null;
        return;
    }
    activeOrderId.value = order.id;
    resultForm.status = order.status === 'pending' ? 'in_progress' : order.status;
    resultForm.result_summary = order.result_summary || '';
    resultForm.result_notes = order.result_notes || '';
    resultForm.clearErrors();
};

const saveOrderUpdate = (orderId) => resultForm.patch(route('lab.update', orderId), {
    preserveScroll: true,
    onSuccess: () => {
        activeOrderId.value = null;
    },
});

const statusBadge = {
    pending: 'bg-warning text-dark',
    in_progress: 'bg-info text-dark',
    completed: 'bg-success',
    cancelled: 'bg-secondary',
};

const priorityBadge = {
    routine: 'bg-light text-secondary border',
    urgent: 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
    stat: 'bg-danger text-white',
};

const formatDate = (value) => value
    ? new Date(value).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
    : '—';
</script>

<template>
    <AppLayout>
        <div class="clinical-page">
            <div class="app-page-header">
                <div class="app-page-header-content">
                    <div class="app-page-eyebrow">Diagnostics</div>
                    <h1 class="app-page-title">Laboratory</h1>
                    <p class="app-page-description">Track specimen requests, processing status, and verified diagnostic results.</p>
                </div>
                <form class="app-page-header-actions" @submit.prevent="applyFilters">
                    <label for="lab-search" class="visually-hidden">Search lab orders</label>
                    <input id="lab-search" v-model="search" class="form-control" placeholder="Search test, patient, or MRN">
                    <select v-model="statusFilter" class="form-select" style="width: auto;" aria-label="Filter by status" @change="applyFilters">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search" /></button>
                </form>
            </div>

            <section class="row g-3 mb-4" aria-label="Laboratory summary">
                <div class="col-sm-6 col-xl-3">
                    <div class="kpi-tile kpi-tile-blue h-100">
                        <span class="kpi-tile-icon"><i class="bi bi-clipboard2-pulse" /></span>
                        <div>
                            <div class="kpi-tile-label">Total orders</div>
                            <div class="kpi-tile-value">{{ summary.total }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="kpi-tile kpi-tile-amber h-100">
                        <span class="kpi-tile-icon"><i class="bi bi-hourglass-split" /></span>
                        <div>
                            <div class="kpi-tile-label">Pending</div>
                            <div class="kpi-tile-value">{{ summary.pending }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="kpi-tile kpi-tile-teal h-100">
                        <span class="kpi-tile-icon"><i class="bi bi-eyedropper" /></span>
                        <div>
                            <div class="kpi-tile-label">In progress</div>
                            <div class="kpi-tile-value">{{ summary.inProgress }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="kpi-tile kpi-tile-green h-100">
                        <span class="kpi-tile-icon"><i class="bi bi-check2-circle" /></span>
                        <div>
                            <div class="kpi-tile-label">Completed</div>
                            <div class="kpi-tile-value">{{ summary.completed }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <div v-if="canManage" class="card clinical-panel mb-4">
                <div class="card-header">
                    <h2><i class="bi bi-plus-circle me-2 text-primary" />New lab test order</h2>
                </div>
                <div class="card-body">
                    <form @submit.prevent="createOrder">
                        <div class="row g-3 align-items-start">
                            <div class="col-lg-4">
                                <label for="lab_patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                                <PatientSearchSelect
                                    id="lab_patient_id"
                                    v-model="orderForm.patient_id"
                                    :patients="patients"
                                    placeholder="Select patient..."
                                    :error="orderForm.errors.patient_id"
                                    :required="true"
                                />
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="lab-test-name" class="form-label">Test / Investigation <span class="text-danger">*</span></label>
                                <input id="lab-test-name" v-model="orderForm.test_name" class="form-control" :class="{ 'is-invalid': orderForm.errors.test_name }" placeholder="e.g. Full Blood Count" required>
                                <div class="invalid-feedback">{{ orderForm.errors.test_name }}</div>
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label for="lab-category" class="form-label">Category</label>
                                <input id="lab-category" v-model="orderForm.category" class="form-control" :class="{ 'is-invalid': orderForm.errors.category }" placeholder="Hematology">
                                <div class="invalid-feedback">{{ orderForm.errors.category }}</div>
                            </div>
                            <div class="col-md-3 col-lg-3">
                                <label for="lab-priority" class="form-label">Priority</label>
                                <select id="lab-priority" v-model="orderForm.priority" class="form-select" :class="{ 'is-invalid': orderForm.errors.priority }">
                                    <option value="routine">Routine</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="stat">STAT</option>
                                </select>
                                <div class="invalid-feedback">{{ orderForm.errors.priority }}</div>
                            </div>
                            <div class="col-md-9">
                                <label for="lab-notes" class="form-label">Clinical indication / notes</label>
                                <input id="lab-notes" v-model="orderForm.notes" class="form-control" :class="{ 'is-invalid': orderForm.errors.notes }" placeholder="Optional specimen or clinical notes">
                                <div class="invalid-feedback">{{ orderForm.errors.notes }}</div>
                            </div>
                            <div class="col-md-3 d-grid align-self-end">
                                <button type="submit" class="btn btn-primary" :disabled="orderForm.processing">
                                    <span v-if="orderForm.processing" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
                                    Submit order
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card clinical-panel">
                <div class="card-header">
                    <h2>Laboratory queue</h2>
                    <span class="small text-muted">{{ orders.total }} order{{ orders.total === 1 ? '' : 's' }}</span>
                </div>
                <div class="list-group list-group-flush">
                    <div v-for="order in orders.data" :key="order.id" class="list-group-item p-3">
                        <div class="d-flex flex-wrap justify-content-between gap-3">
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <span class="fw-bold">{{ order.test_name }}</span>
                                    <span v-if="order.category" class="badge bg-light text-secondary border">{{ order.category }}</span>
                                    <span class="badge text-uppercase" :class="priorityBadge[order.priority] || 'bg-light text-dark'">{{ order.priority }}</span>
                                    <span class="badge text-capitalize" :class="statusBadge[order.status] || 'bg-secondary'">{{ order.status.replace('_', ' ') }}</span>
                                </div>
                                <div class="small text-muted">
                                    <Link :href="route('patients.show', order.patient.id)" class="fw-semibold text-decoration-none">
                                        {{ order.patient.first_name }} {{ order.patient.last_name }}
                                    </Link>
                                    · <code>{{ order.patient.mrn }}</code>
                                    · Ordered {{ formatDate(order.ordered_at) }}
                                    <span v-if="order.ordered_by"> by {{ order.ordered_by.name }}</span>
                                </div>
                                <div v-if="order.notes" class="small mt-2 text-muted"><strong>Indication:</strong> {{ order.notes }}</div>
                                <div v-if="order.result_summary || order.result_notes" class="mt-2 rounded bg-light border p-2 small">
                                    <div v-if="order.result_summary"><strong>Result:</strong> {{ order.result_summary }}</div>
                                    <div v-if="order.result_notes" class="text-muted mt-1">{{ order.result_notes }}</div>
                                    <div v-if="order.processed_by" class="text-muted mt-1">
                                        Verified by {{ order.processed_by.name }}<span v-if="order.completed_at"> · {{ formatDate(order.completed_at) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="canManage" class="align-self-start">
                                <button type="button" class="btn btn-sm" :class="activeOrderId === order.id ? 'btn-outline-secondary' : 'btn-outline-primary'" @click="openResultEditor(order)">
                                    {{ activeOrderId === order.id ? 'Close' : 'Update / enter result' }}
                                </button>
                            </div>
                        </div>

                        <form v-if="canManage && activeOrderId === order.id" class="border-top mt-3 pt-3" @submit.prevent="saveOrderUpdate(order.id)">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label :for="`order-status-${order.id}`" class="form-label">Status</label>
                                    <select :id="`order-status-${order.id}`" v-model="resultForm.status" class="form-select">
                                        <option value="pending">Pending</option>
                                        <option value="in_progress">In progress</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-9">
                                    <label :for="`order-summary-${order.id}`" class="form-label">Result summary</label>
                                    <input :id="`order-summary-${order.id}`" v-model="resultForm.result_summary" class="form-control" :class="{ 'is-invalid': resultForm.errors.result_summary }" placeholder="e.g. Hb 12.4 g/dL, WBC 6.8 x10^9/L">
                                    <div class="invalid-feedback">{{ resultForm.errors.result_summary }}</div>
                                </div>
                                <div class="col-12">
                                    <label :for="`order-notes-${order.id}`" class="form-label">Detailed findings / reference notes</label>
                                    <textarea :id="`order-notes-${order.id}`" v-model="resultForm.result_notes" rows="2" class="form-control" :class="{ 'is-invalid': resultForm.errors.result_notes }" />
                                    <div class="invalid-feedback">{{ resultForm.errors.result_notes }}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-success btn-sm" :disabled="resultForm.processing">
                                        <i class="bi bi-check2-circle me-1" /> Save lab update
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div v-if="orders.data.length === 0" class="text-center py-5 text-muted">
                        No laboratory orders match your filters.
                    </div>
                </div>
                <div v-if="orders.last_page > 1" class="card-footer bg-white">
                    <Pagination :paginator="orders" aria-label="Laboratory pagination" show-summary />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
