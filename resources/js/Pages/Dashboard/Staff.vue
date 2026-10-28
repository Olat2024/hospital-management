<!-- resources/js/Pages/Dashboard/Staff.vue -->
<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    stats: Object,
    recentAppointments: Array
});

const page = usePage();
const roles = computed(() => page.props.auth.roles || []);
const userName = computed(() => page.props.auth.user?.name || 'there');
const greetingName = computed(() => (
    roles.value.includes('Doctor') && !/^Dr\.?\s/i.test(userName.value)
        ? `Dr. ${userName.value}`
        : userName.value
));

const statusBadge = {
    scheduled: 'bg-primary',
    completed: 'bg-success',
    cancelled: 'bg-secondary',
    no_show: 'bg-danger',
};
</script>

<template>
    <AppLayout>
        <div class="staff-dashboard">
            <header class="dashboard-heading">
                <div>
                    <div class="dashboard-eyebrow">Hospital overview</div>
                    <h1 class="dashboard-title">Welcome back, {{ greetingName }}</h1>
                    <p class="text-muted mb-0">Here is your overview for today.</p>
                </div>
                <div class="dashboard-date">
                    <i class="bi bi-calendar3 me-2"></i>{{ new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' }) }}
                </div>
            </header>

            <section class="row g-3 mb-4" aria-label="Hospital statistics">
                <div class="col-sm-6 col-xl-3">
                    <article class="stat-card stat-blue h-100">
                        <span class="stat-icon"><i class="bi bi-calendar-check"></i></span>
                        <span class="stat-period">Today</span>
                        <div class="stat-content">
                            <div class="stat-label">Today's appointments</div>
                            <div class="stat-value">{{ stats.todayAppointments }}</div>
                            <div class="stat-caption">Scheduled across the hospital</div>
                        </div>
                    </article>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <article class="stat-card stat-amber h-100">
                        <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
                        <span class="stat-period">Today</span>
                        <div class="stat-content">
                            <div class="stat-label">Waiting</div>
                            <div class="stat-value">{{ stats.waiting }}</div>
                            <div class="stat-caption">Appointments yet to be seen</div>
                        </div>
                    </article>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <article class="stat-card stat-green h-100">
                        <span class="stat-icon"><i class="bi bi-people"></i></span>
                        <span class="stat-period">Registered</span>
                        <div class="stat-content">
                            <div class="stat-label">Patients</div>
                            <div class="stat-value">{{ stats.totalPatients }}</div>
                            <div class="stat-caption">Patients in your directory</div>
                        </div>
                    </article>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <article class="stat-card stat-violet h-100">
                        <span class="stat-icon"><i class="bi bi-person-badge"></i></span>
                        <span class="stat-period">Care team</span>
                        <div class="stat-content">
                            <div class="stat-label">Active doctors</div>
                            <div class="stat-value">{{ stats.doctors }}</div>
                            <div class="stat-caption">Available clinical staff</div>
                        </div>
                    </article>
                </div>
            </section>

            <section class="schedule-card card">
                <div class="card-header">
                    <div>
                        <div class="schedule-eyebrow">Your daily schedule</div>
                        <h2 class="schedule-title">Upcoming appointments</h2>
                    </div>
                    <Link :href="route('appointments.index')" class="btn btn-primary btn-sm schedule-action">
                        View schedule <i class="bi bi-arrow-right ms-1"></i>
                    </Link>
                </div>
                <div class="table-responsive">
                    <table class="table dashboard-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="appointment in recentAppointments" :key="appointment.id">
                                <td><strong>{{ appointment.scheduled_time }}</strong></td>
                                <td>
                                    <template v-if="appointment.patient">
                                        {{ appointment.patient.first_name }} {{ appointment.patient.last_name }}
                                        <div class="small text-muted"><code>{{ appointment.patient.mrn }}</code></div>
                                    </template>
                                    <span v-else class="text-muted">Unknown</span>
                                </td>
                                <td>
                                    <template v-if="appointment.doctor">
                                        Dr. {{ appointment.doctor.first_name }} {{ appointment.doctor.last_name }}
                                    </template>
                                    <span v-else class="text-muted">Unassigned</span>
                                </td>
                                <td>
                                    <span class="badge" :class="statusBadge[appointment.status] || 'bg-secondary'">
                                        {{ appointment.status }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <Link
                                        v-if="appointment.patient"
                                        :href="route('patients.show', appointment.patient.id)"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Open chart
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="recentAppointments.length === 0">
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar2-x empty-icon"></i>
                                    <div class="fw-semibold mt-2">No appointments scheduled for today</div>
                                    <div class="small">Your schedule will appear here when appointments are booked.</div>
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
.staff-dashboard {
    --dashboard-ink: #203047;
    --dashboard-muted: #78879a;
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

.stat-card {
    align-items: center;
    background: #fff;
    border: 1px solid var(--dashboard-border);
    border-left-width: 3px;
    border-radius: 0.75rem;
    box-shadow: 0 3px 12px rgb(34 56 84 / 4%);
    display: flex;
    gap: 0.8rem;
    min-height: 98px;
    padding: 0.85rem 0.95rem;
    position: relative;
}

.stat-icon {
    align-items: center;
    border-radius: 0.65rem;
    display: inline-flex;
    flex: 0 0 auto;
    font-size: 1rem;
    height: 38px;
    justify-content: center;
    width: 38px;
}

.stat-period {
    background: #f3f6f9;
    border-radius: 1rem;
    color: #78879a;
    font-size: 0.62rem;
    line-height: 1;
    padding: 0.3rem 0.45rem;
    position: absolute;
    right: 0.7rem;
    top: 0.65rem;
}

.stat-blue { border-left-color: #2878d0; }
.stat-amber { border-left-color: #cc851d; }
.stat-green { border-left-color: #16956a; }
.stat-violet { border-left-color: #7855c4; }
.stat-blue .stat-icon { background: #eaf3ff; color: #2878d0; }
.stat-amber .stat-icon { background: #fff4e3; color: #cc851d; }
.stat-green .stat-icon { background: #e8f7f1; color: #16956a; }
.stat-violet .stat-icon { background: #f1edff; color: #7855c4; }

.stat-content {
    min-width: 0;
    padding-top: 0.4rem;
}

.stat-label {
    color: #78879a;
    font-size: 0.72rem;
    font-weight: 600;
    line-height: 1.25;
}

.stat-value {
    font-size: 1.65rem;
    font-weight: 700;
    letter-spacing: -0.045em;
    line-height: 1.1;
    margin-top: 0.18rem;
}

.stat-caption {
    color: #8795a7;
    font-size: 0.65rem;
    line-height: 1.3;
    margin-top: 0.25rem;
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

.schedule-action {
    border-radius: 0.55rem;
    font-weight: 600;
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

@media (max-width: 575.98px) {
    .staff-dashboard {
        padding-top: 0.75rem;
    }

    .schedule-card .card-header {
        padding: 0.9rem 1rem;
    }
}
</style>
