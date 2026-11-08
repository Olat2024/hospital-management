<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    cards: Object,
});
</script>

<template>
    <AppLayout>
        <div class="clinical-page">
            <header class="clinical-page-heading">
                <div>
                    <div class="clinical-eyebrow">Maternal care</div>
                    <h1 class="clinical-page-title">ANC cards</h1>
                    <p class="clinical-page-subtitle">{{ cards.total }} ante-natal card<span v-if="cards.total !== 1">s</span></p>
                </div>
                <Link :href="route('antenatal.create')" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Register ANC card
                </Link>
            </header>

            <section class="card clinical-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Patient / I.P. number</th>
                                <th>Phone</th>
                                <th>Date of booking</th>
                                <th>Pregnancy at booking</th>
                                <th>Visits</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="card in cards.data" :key="card.id">
                                <td>
                                    <div class="fw-semibold">{{ card.patient.last_name }}, {{ card.patient.first_name }}</div>
                                    <code>{{ card.patient.mrn }}</code>
                                </td>
                                <td>{{ card.patient.phone || '—' }}</td>
                                <td>{{ card.booking_date ? new Date(card.booking_date).toLocaleDateString() : '—' }}</td>
                                <td>{{ card.pregnancy_duration_at_booking || '—' }}</td>
                                <td>{{ card.visits_count }}</td>
                                <td class="text-end">
                                    <Link :href="route('antenatal.show', card.id)" class="btn btn-sm btn-outline-primary">
                                        View ANC card <i class="bi bi-arrow-right"></i>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!cards.data.length">
                                <td colspan="6" class="text-center text-muted py-5">
                                    No ANC cards have been registered.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="cards.last_page > 1" class="card-footer bg-white d-flex justify-content-center">
                    <nav aria-label="ANC card pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li v-for="link in cards.links" :key="link.label" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                                <Link class="page-link" :href="link.url || '#!'" :preserve-scroll="true" v-html="link.label"></Link>
                            </li>
                        </ul>
                    </nav>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
