<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    doctor: Object,
    appointments: Array
});

const page = usePage();
const loginName = computed(() => page.props.auth.user?.name || 'there');
const greetingName = computed(() => (
    /^Dr\.?\s/i.test(loginName.value) ? loginName.value : `Dr. ${loginName.value}`
));
</script>

<template>
    <AppLayout>
        <div class="doctor-dashboard">
            <header class="dashboard-heading">
                <div>
                    <div class="dashboard-eyebrow">Clinical workspace</div>
                    <h1 class="dashboard-title">Welcome back, {{ greetingName }}</h1>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <p class="text-muted mb-0">Here is your schedule for today.</p>
                        <span v-if="doctor?.specialization" class="specialty-pill">{{ doctor.specialization }}</span>
                    </div>
                </div>
                <div class="dashboard-date">
                    <i class="bi bi-calendar3 me-2"></i>{{ new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' }) }}
                </div>
            </header>

            <section class="doctor-summary row g-3 mb-4" aria-label="Schedule summary">
                <div class="col-sm-6">
                    <article class="summary-card">
                        <span class="summary-icon summary-blue"><i class="bi bi-calendar-check"></i></span>
                        <div>
                            <span class="summary-label">Today's appointments</span>
                            <strong>{{ appointments?.length || 0 }}</strong>
                        </div>
                    </article>
                </div>
                <div class="col-sm-6">
                    <article class="summary-card">
                        <span class="summary-icon summary-green"><i class="bi bi-person-check"></i></span>
                        <div>
                            <span class="summary-label">Waiting to be seen</span>
                            <strong>{{ props.appointments?.filter((appointment) => appointment.status === 'scheduled').length || 0 }}</strong>
                        </div>
                    </article>
                </div>
            </section>

            <section class="schedule-card card">
                <div class="card-header">
                    <div>
                        <div class="schedule-eyebrow">Your daily schedule</div>
                        <h2 class="schedule-title">Today's patient schedule</h2>
                    </div>
                    <span class="appointment-count">{{ appointments?.length || 0 }} appointment{{ appointments?.length === 1 ? '' : 's' }}</span>
                </div>
                <div v-if="!doctor" class="alert alert-warning mx-3 mt-3 mb-0">
                    Your account is not linked to a doctor profile. Please contact an administrator.
                </div>
                <div class="table-responsive">
                    <table class="table dashboard-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Patient name</th>
                                <th>Phone number</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="appointment in appointments" :key="appointment.id">
                                <td><strong>{{ appointment.scheduled_time }}</strong></td>
                                <td>{{ appointment.patient.first_name }} {{ appointment.patient.last_name }}</td>
                                <td>{{ appointment.patient.phone || '—' }}</td>
                                <td>
                                    <span class="badge" :class="appointment.status === 'completed' ? 'bg-success' : appointment.status === 'cancelled' ? 'bg-secondary' : 'bg-primary'">
                                        {{ appointment.status }}
                                    </span>
                                </td>
                                <td>
                                    <Link :href="route('patients.show', appointment.patient.id)" class="btn btn-sm btn-outline-primary">
                                        View chart
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!appointments || appointments.length === 0">
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar2-x empty-icon"></i>
                                    <div class="fw-semibold mt-2">No appointments scheduled for today</div>
                                    <div class="small">Your patient schedule will appear here when appointments are booked.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.doctor-dashboard {
    --dashboard-ink: #203047;
    --dashboard-border: #e8edf3;
    color: var(--dashboard-ink);
    padding: 1.25rem 0 2rem;
}

.dashboard-heading {
    align-items: end;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

.dashboard-eyebrow,
.schedule-eyebrow {
    color: #2878d0;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.11em;
    margin-bottom: 0.3rem;
    text-transform: uppercase;
}

.dashboard-title {
    font-size: clamp(1.55rem, 3vw, 2rem);
    font-weight: 700;
    letter-spacing: -0.035em;
    margin-bottom: 0.35rem;
}

.dashboard-date {
    background: #fff;
    border: 1px solid var(--dashboard-border);
    border-radius: 0.65rem;
    color: #64758a;
    font-size: 0.8rem;
    padding: 0.65rem 0.85rem;
}

.specialty-pill,
.appointment-count {
    background: #eaf3ff;
    border-radius: 2rem;
    color: #3974b2;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.35rem 0.65rem;
}

.summary-card {
    align-items: center;
    background: #fff;
    border: 1px solid var(--dashboard-border);
    border-radius: 0.75rem;
    box-shadow: 0 3px 12px rgb(34 56 84 / 4%);
    display: flex;
    gap: 0.75rem;
    min-height: 78px;
    padding: 0.75rem 0.9rem;
}

.summary-icon {
    align-items: center;
    border-radius: 0.65rem;
    display: inline-flex;
    flex: 0 0 auto;
    font-size: 1rem;
    height: 38px;
    justify-content: center;
    width: 38px;
}

.summary-blue { background: #eaf3ff; color: #2878d0; }
.summary-green { background: #e8f7f1; color: #16956a; }

.summary-label {
    color: #78879a;
    display: block;
    font-size: 0.72rem;
    font-weight: 600;
}

.summary-card strong {
    display: block;
    font-size: 1.45rem;
    letter-spacing: -0.04em;
    line-height: 1.2;
    margin-top: 0.2rem;
}

.schedule-card {
    border: 1px solid var(--dashboard-border);
    border-radius: 0.9rem;
    box-shadow: 0 5px 20px rgb(34 56 84 / 4%);
    overflow: hidden;
}

.schedule-card .card-header {
    align-items: center;
    background: #fff;
    border-bottom: 1px solid #eef1f5;
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    justify-content: space-between;
    padding: 1rem 1.25rem;
}

.schedule-eyebrow {
    color: #8a98a9;
    font-size: 0.64rem;
    margin-bottom: 0.2rem;
}

.schedule-title {
    font-size: 1rem;
    font-weight: 700;
    margin: 0;
}

.dashboard-table {
    color: #536478;
    font-size: 0.8rem;
}

.dashboard-table thead th {
    background: #f8fafc;
    border-bottom: 1px solid #e8edf3;
    color: #8492a3;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    padding: 0.8rem 1rem;
    text-transform: uppercase;
    white-space: nowrap;
}

.dashboard-table tbody td {
    border-color: #f0f3f6;
    padding: 0.85rem 1rem;
}

.empty-icon {
    color: #b6c4d2;
    font-size: 1.5rem;
}
</style>
