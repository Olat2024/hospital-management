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
                    <div class="clinical-eyebrow">Patient directory</div>
                    <h1 class="clinical-page-title">Family cards</h1>
                    <p class="clinical-page-subtitle">Family cards and the patient members linked to each one.</p>
                </div>
                <Link :href="route('family-cards.create')" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> New family card
                </Link>
            </header>

            <section class="card clinical-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Card number</th>
                                <th>Family</th>
                                <th>Primary contact</th>
                                <th>Phone</th>
                                <th>Members</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="card in cards.data" :key="card.id">
                                <td><code>{{ card.card_number }}</code></td>
                                <td class="fw-semibold">{{ card.family_name }}</td>
                                <td>{{ card.contact_name || '—' }}</td>
                                <td>{{ card.phone || '—' }}</td>
                                <td>
                                    <div v-if="card.patients.length" class="d-flex flex-column gap-1">
                                        <Link
                                            v-for="patient in card.patients"
                                            :key="patient.id"
                                            :href="route('patients.show', patient.id)"
                                            class="text-decoration-none"
                                        >
                                            {{ patient.first_name }} {{ patient.last_name }} <span class="text-muted">({{ patient.mrn }})</span>
                                        </Link>
                                    </div>
                                    <span v-else class="text-muted">No patients linked yet</span>
                                </td>
                                <td class="text-end">
                                    <Link
                                        :href="route('patients.create.individual', { family_card_id: card.id })"
                                        class="btn btn-sm btn-outline-primary text-nowrap"
                                    >
                                        <i class="bi bi-person-plus me-1"></i> Add patient
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!cards.data.length">
                                <td colspan="6" class="text-center text-muted py-4">No family cards have been registered.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="cards.last_page > 1" class="card-footer bg-white d-flex justify-content-center">
                    <nav aria-label="Family card pagination">
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
