<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    can: Object,
});

const selection = ref('');

const openRegistration = () => {
    const destinations = {
        individual: route('patients.create.individual'),
        family: route('family-cards.create'),
        anc: route('antenatal.create'),
    };

    if (destinations[selection.value]) {
        router.visit(destinations[selection.value]);
    }
};
</script>

<template>
    <AppLayout>
        <div class="clinical-page">
            <header class="clinical-page-heading">
                <div>
                    <div class="clinical-eyebrow">Patient directory</div>
                    <h1 class="clinical-page-title">New registration</h1>
                    <p class="clinical-page-subtitle">Choose the type of record to create.</p>
                </div>
                <Link :href="route('patients.index')" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to patients
                </Link>
            </header>

            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <section class="card clinical-form-card">
                        <div class="card-header">
                            <h2 class="h5 mb-0"><i class="bi bi-ui-checks-grid me-2"></i>Choose a registration</h2>
                        </div>
                        <div class="card-body">
                            <label for="registration-type" class="form-label">Registration type</label>
                            <select
                                id="registration-type"
                                v-model="selection"
                                class="form-select form-select-lg"
                                @change="openRegistration"
                            >
                                <option value="" disabled>Select a form...</option>
                                <option value="individual">Individual patient</option>
                                <option v-if="can.manageFamilyCards" value="family">Family card</option>
                                <option v-if="can.manageAnc" value="anc">ANC (Ante-Natal Card)</option>
                            </select>
                            <p class="form-text mt-3 mb-0">
                                Each form opens separately. Register a patient before creating an ANC card for them.
                            </p>
                        </div>
                    </section>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <Link :href="route('patients.create.individual')" class="btn btn-outline-primary">
                            Individual patient
                        </Link>
                        <Link v-if="can.manageFamilyCards" :href="route('family-cards.create')" class="btn btn-outline-primary">
                            Family card
                        </Link>
                        <Link v-if="can.manageAnc" :href="route('antenatal.create')" class="btn btn-outline-primary">
                            ANC card
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
