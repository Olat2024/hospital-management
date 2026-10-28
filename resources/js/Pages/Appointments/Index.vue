<!-- resources/js/Pages/Appointments/Index.vue -->
<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmAction } from '@/Composables/useSweetAlert';

const props = defineProps({
    appointments: Object,
    doctors: Array,
    filters: Object,
    summary: Object,
    can: Object
});

const search = ref(props.filters.search);
const date = ref(props.filters.date);
const doctorId = ref(props.filters.doctor_id || '');
const status = ref(props.filters.status);

const applyFilters = (page = 1) => {
    router.get(route('appointments.index'), {
        search: search.value || undefined,
        date: date.value || undefined,
        doctor_id: doctorId.value || undefined,
        status: status.value || undefined,
        page: page > 1 ? page : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const statusBadge = {
    scheduled: 'bg-primary',
    completed: 'bg-success',
    cancelled: 'bg-secondary',
    no_show: 'bg-danger',
};

const statusLabel = {
    scheduled: 'Scheduled',
    completed: 'Completed',
    cancelled: 'Cancelled',
    no_show: 'No-show',
};

const changeStatus = (appointment, newStatus) => {
    router.put(route('appointments.update', appointment.id), { status: newStatus }, {
        preserveScroll: true,
    });
};

const cancel = async (appointment) => {
    const confirmed = await confirmAction({
        title: 'Cancel this appointment?',
        text: 'The appointment will be marked as cancelled.',
        confirmButtonText: 'Cancel appointment',
    });

    if (confirmed) changeStatus(appointment, 'cancelled');
};

// ---- Reschedule modal ----
const rescheduling = ref(null);
const rescheduleForm = useForm({
    status: 'scheduled',
    scheduled_date: '',
    scheduled_time: '',
});

const openReschedule = (appointment) => {
    rescheduling.value = appointment;
    rescheduleForm.status = 'scheduled';
    rescheduleForm.scheduled_date = props.filters.date || new Date().toISOString().slice(0, 10);
    rescheduleForm.scheduled_time = appointment.scheduled_time
        ? to24h(appointment.scheduled_time)
        : '09:00';
};

const submitReschedule = () => {
    rescheduleForm.put(route('appointments.update', rescheduling.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            rescheduling.value = null;
            rescheduleForm.reset();
        },
    });
};

// "9:30 AM" → "09:30" for the time input.
const to24h = (time) => {
    if (!time) return '';
    if (!time.includes('AM') && !time.includes('PM')) return time.slice(0, 5);
    const [clock, meridiem] = time.split(' ');
    let [h, m] = clock.split(':').map(Number);
    if (meridiem === 'PM' && h !== 12) h += 12;
    if (meridiem === 'AM' && h === 12) h = 0;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
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

const summaryCards = computed(() => [
    { label: 'Total', value: props.summary.total, icon: 'bi-calendar-week', tone: 'tone-blue' },
    { label: 'Scheduled', value: props.summary.scheduled, icon: 'bi-clock-history', tone: 'tone-teal' },
    { label: 'Completed', value: props.summary.completed, icon: 'bi-check-circle', tone: 'tone-green' },
    { label: 'Cancelled', value: props.summary.cancelled, icon: 'bi-calendar-x', tone: 'tone-slate' },
    { label: 'No-show', value: props.summary.no_show, icon: 'bi-person-x', tone: 'tone-amber' },
]);
</script>

<template>
    <AppLayout>
        <div class="clinical-page appointments-page">
        <div class="clinical-page-heading">
            <div>
                <div class="clinical-eyebrow">Patient care coordination</div>
                <h1 class="clinical-page-title">Appointment schedule</h1>
                <p class="clinical-page-subtitle">Manage bookings, appointment status, and reschedules.</p>
            </div>
            <Link v-if="can.book" :href="route('appointments.create')" class="btn btn-primary px-3 py-2">
                <i class="bi bi-calendar-plus me-1"></i> Book Appointment
            </Link>
        </div>

        <!-- Summary strip -->
        <div class="row g-3 mb-4 appointment-summary">
            <div class="col" v-for="card in summaryCards" :key="card.label">
                <article class="appointment-stat-card h-100" :class="card.tone">
                    <span class="appointment-stat-icon"><i class="bi" :class="card.icon"></i></span>
                    <div>
                        <div class="appointment-stat-label">{{ card.label }}</div>
                        <div class="appointment-stat-value">{{ card.value }}</div>
                    </div>
                </article>
            </div>
        </div>

        <!-- Filters -->
        <div class="card clinical-panel mb-4">
            <div class="card-body py-3">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters()">
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input id="date" type="date" class="form-control" v-model="date">
                    </div>
                    <div class="col-md-3">
                        <label for="doctor" class="form-label">Doctor</label>
                        <select id="doctor" class="form-select" v-model="doctorId">
                            <option value="">All doctors</option>
                            <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                                Dr. {{ doctor.last_name }} ({{ doctor.specialization }})
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" class="form-select" v-model="status">
                            <option value="">All</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="no_show">No-show</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="search" class="form-label">Patient</label>
                        <input id="search" type="text" class="form-control" placeholder="Name or MRN" v-model="search">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">Apply</button>
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            @click="date = ''; doctorId = ''; status = ''; search = ''; applyFilters()"
                        >
                            All
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card clinical-panel">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="appointment in appointments.data" :key="appointment.id">
                            <td><strong>{{ appointment.scheduled_date }}</strong></td>
                            <td>{{ appointment.scheduled_time }}</td>
                            <td>
                                <Link
                                    v-if="appointment.patient"
                                    :href="route('patients.show', appointment.patient.id)"
                                    class="text-decoration-none fw-semibold"
                                >
                                    {{ appointment.patient.last_name }}, {{ appointment.patient.first_name }}
                                </Link>
                                <span v-else class="text-muted">Unknown</span>
                                <div class="small text-muted" v-if="appointment.patient">
                                    <code>{{ appointment.patient.mrn }}</code>
                                </div>
                            </td>
                            <td>
                                <template v-if="appointment.doctor">
                                    Dr. {{ appointment.doctor.first_name }} {{ appointment.doctor.last_name }}
                                    <div class="small text-muted">{{ appointment.doctor.specialization }}</div>
                                </template>
                                <span v-else class="text-muted">Unassigned</span>
                            </td>
                            <td class="text-truncate" style="max-width: 220px;" :title="appointment.reason">
                                {{ appointment.reason || '—' }}
                            </td>
                            <td>
                                <span class="badge" :class="statusBadge[appointment.status] || 'bg-secondary'">
                                    {{ statusLabel[appointment.status] || appointment.status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group" v-if="can.manage">
                                    <button
                                        v-if="appointment.status === 'scheduled'"
                                        type="button"
                                        class="btn btn-sm btn-outline-success"
                                        title="Mark completed"
                                        @click="changeStatus(appointment, 'completed')"
                                    >
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <button
                                        v-if="appointment.status === 'scheduled'"
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Mark no-show"
                                        @click="changeStatus(appointment, 'no_show')"
                                    >
                                        <i class="bi bi-person-x"></i>
                                    </button>
                                    <button
                                        v-if="appointment.status === 'scheduled'"
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Reschedule"
                                        @click="openReschedule(appointment)"
                                    >
                                        <i class="bi bi-clock-history"></i>
                                    </button>
                                    <button
                                        v-if="appointment.status === 'scheduled'"
                                        type="button"
                                        class="btn btn-sm btn-outline-dark"
                                        title="Cancel appointment"
                                        @click="cancel(appointment)"
                                    >
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                    <button
                                        v-if="appointment.status !== 'scheduled'"
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Reopen as scheduled"
                                        @click="changeStatus(appointment, 'scheduled')"
                                    >
                                        Reopen
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="appointments.data.length === 0">
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar2-x d-block fs-3 mb-2"></i>
                                No appointments match these filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3" v-if="appointments.last_page > 1">
                <span class="small text-muted">
                    Showing {{ appointments.from }}&ndash;{{ appointments.to }} of {{ appointments.total }}
                </span>
                <div class="btn-group">
                    <button
                        v-for="(link, i) in appointments.links"
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

        <!-- Reschedule modal -->
        <div class="modal fade" :class="{ show: !!rescheduling }" tabindex="-1" :style="rescheduling ? 'display: block' : ''">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form @submit.prevent="submitReschedule">
                        <div class="modal-header">
                            <h5 class="modal-title">Reschedule Appointment</h5>
                            <button type="button" class="btn-close" @click="rescheduling = null"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted" v-if="rescheduling">
                                {{ rescheduling.patient?.first_name }} {{ rescheduling.patient?.last_name }}
                                &middot; currently {{ rescheduling.scheduled_date }} at {{ rescheduling.scheduled_time }}
                            </p>
                            <div class="mb-3">
                                <label for="reschedule_date" class="form-label">New date</label>
                                <input
                                    id="reschedule_date"
                                    type="date"
                                    class="form-control"
                                    :class="{ 'is-invalid': rescheduleForm.errors.scheduled_date }"
                                    v-model="rescheduleForm.scheduled_date"
                                    required
                                >
                                <div class="invalid-feedback" v-if="rescheduleForm.errors.scheduled_date">
                                    {{ rescheduleForm.errors.scheduled_date }}
                                </div>
                            </div>
                            <div class="mb-0">
                                <label for="reschedule_time" class="form-label">New time</label>
                                <input
                                    id="reschedule_time"
                                    type="time"
                                    class="form-control"
                                    :class="{ 'is-invalid': rescheduleForm.errors.scheduled_time }"
                                    v-model="rescheduleForm.scheduled_time"
                                    required
                                >
                                <div class="invalid-feedback" v-if="rescheduleForm.errors.scheduled_time">
                                    {{ rescheduleForm.errors.scheduled_time }}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="rescheduling = null">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="rescheduleForm.processing">
                                <span v-if="rescheduleForm.processing" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                Save new time
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-backdrop fade" :class="{ show: !!rescheduling }" v-if="rescheduling"></div>
        </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.appointment-summary > .col {
    flex: 0 0 auto;
    width: 20%;
}

.appointment-stat-card {
    align-items: center;
    background: #fff;
    border: 1px solid #e6edf3;
    border-radius: 0.8rem;
    box-shadow: 0 5px 20px rgb(34 56 84 / 4%);
    display: flex;
    gap: 0.75rem;
    min-height: 92px;
    padding: 0.9rem;
}

.appointment-stat-icon {
    align-items: center;
    border-radius: 0.65rem;
    display: inline-flex;
    flex: 0 0 auto;
    font-size: 1.05rem;
    height: 40px;
    justify-content: center;
    width: 40px;
}

.tone-blue .appointment-stat-icon { background: #eaf3ff; color: #2878d0; }
.tone-teal .appointment-stat-icon { background: #e6f6f5; color: #168b89; }
.tone-green .appointment-stat-icon { background: #e8f7f1; color: #16956a; }
.tone-slate .appointment-stat-icon { background: #eff2f5; color: #64758a; }
.tone-amber .appointment-stat-icon { background: #fff4e3; color: #cc851d; }

.appointment-stat-label {
    color: #78879a;
    font-size: 0.72rem;
    font-weight: 600;
}

.appointment-stat-value {
    color: #203047;
    font-size: 1.45rem;
    font-weight: 700;
    letter-spacing: -0.04em;
    line-height: 1.25;
}

@media (max-width: 1199.98px) {
    .appointment-summary > .col {
        width: 33.3333%;
    }
}

@media (max-width: 767.98px) {
    .appointment-summary > .col {
        width: 50%;
    }
}

@media (max-width: 575.98px) {
    .appointment-summary > .col {
        width: 100%;
    }
}
</style>
