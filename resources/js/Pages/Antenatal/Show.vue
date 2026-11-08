<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    card: Object,
    canManage: Boolean,
});

const history = props.card.previous_medical_history || {};
const previousPregnancies = props.card.previous_pregnancies || [];

const form = useForm({
    visit_date: '',
    fundal_height_cm: '',
    presentation_position: '',
    presenting_part_relation: '',
    foetal_heart: '',
    urine: '',
    blood_pressure: '',
    weight_kg: '',
    pcv: '',
    oedema: '',
    remarks: '',
    examiner_initials: '',
    return_date: '',
});

const dateLabel = (value) => value ? new Date(value).toLocaleDateString() : '—';
const submitVisit = () => form.post(route('antenatal.visits.store', props.card.id), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
});
</script>

<template>
    <AppLayout>
        <div class="clinical-page">
            <header class="clinical-page-heading">
                <div>
                    <div class="clinical-eyebrow">Maternal care</div>
                    <h1 class="clinical-page-title">ANC card</h1>
                    <p class="clinical-page-subtitle">
                        {{ card.patient.last_name }}, {{ card.patient.first_name }} · <code>{{ card.patient.mrn }}</code>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <Link :href="route('antenatal.index')" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> All ANC cards
                    </Link>
                    <Link :href="route('patients.show', card.patient.id)" class="btn btn-outline-primary">Patient chart</Link>
                </div>
            </header>

            <section class="card clinical-form-card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Ante-natal card details</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><span class="text-muted small d-block">Patient</span><strong>{{ card.patient.last_name }}, {{ card.patient.first_name }}</strong></div>
                        <div class="col-md-4"><span class="text-muted small d-block">I.P. number</span><code>{{ card.patient.mrn }}</code></div>
                        <div class="col-md-4"><span class="text-muted small d-block">Phone</span>{{ card.patient.phone || '—' }}</div>
                        <div class="col-md-12"><span class="text-muted small d-block">Address</span>{{ card.patient.address || '—' }}</div>
                        <div class="col-md-4"><span class="text-muted small d-block">Date of booking</span>{{ dateLabel(card.booking_date) }}</div>
                        <div class="col-md-4"><span class="text-muted small d-block">Duration of pregnancy at booking</span>{{ card.pregnancy_duration_at_booking || '—' }}</div>
                        <div class="col-md-4"><span class="text-muted small d-block">Age at booking</span>{{ card.age_at_booking || '—' }}</div>
                        <div class="col-md-4"><span class="text-muted small d-block">Age at marriage</span>{{ card.age_at_marriage || '—' }}</div>
                        <div class="col-md-4"><span class="text-muted small d-block">L.M.P.</span>{{ dateLabel(card.last_menstrual_period) }}</div>
                        <div class="col-md-4"><span class="text-muted small d-block">E.D.D.</span>{{ dateLabel(card.expected_delivery_date) }}</div>
                        <div class="col-md-4"><span class="text-muted small d-block">Tribe</span>{{ card.tribe || '—' }}</div>
                        <div class="col-md-4"><span class="text-muted small d-block">Occupation</span>{{ card.occupation || '—' }}</div>
                        <div class="col-md-4"><span class="text-muted small d-block">Living children</span>{{ card.living_children ?? '—' }}</div>
                        <div class="col-12"><span class="text-muted small d-block">Special points</span>{{ card.special_points || '—' }}</div>
                    </div>
                </div>
            </section>

            <section class="card clinical-panel mb-4">
                <div class="card-header"><h2 class="h5 mb-0">Previous medical history</h2></div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span v-for="field in [
                            ['heart_disease', 'Heart disease'],
                            ['chest_disease', 'Chest disease'],
                            ['kidney_disease', 'Kidney disease'],
                            ['diabetes', 'Diabetes'],
                            ['twinning', 'Twinning'],
                        ]" :key="field[0]" class="badge" :class="history[field[0]] ? 'bg-warning text-dark' : 'bg-light text-secondary border'">
                            {{ field[1] }}: {{ history[field[0]] ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Operations:</strong> {{ history.operations || '—' }}</div>
                        <div class="col-md-6"><strong>Allergies:</strong> {{ history.allergies || '—' }}</div>
                        <div class="col-12"><strong>Other:</strong> {{ history.other || '—' }}</div>
                    </div>
                </div>
            </section>

            <section class="card clinical-panel mb-4">
                <div class="card-header"><h2 class="h5 mb-0">Previous pregnancies ({{ card.previous_pregnancies_total ?? previousPregnancies.length }})</h2></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date of birth</th>
                                <th>Duration</th>
                                <th>Birth weight</th>
                                <th>Pregnancy complications</th>
                                <th>Labour complications</th>
                                <th>Puerperium</th>
                                <th>Baby if dead</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(pregnancy, index) in previousPregnancies" :key="index">
                                <td>{{ dateLabel(pregnancy.date_of_birth) }}</td>
                                <td>{{ pregnancy.duration || '—' }}</td>
                                <td>{{ pregnancy.birth_weight ? `${pregnancy.birth_weight} kg` : '—' }}</td>
                                <td>{{ pregnancy.pregnancy_complications || '—' }}</td>
                                <td>{{ pregnancy.labour_complications || '—' }}</td>
                                <td>{{ pregnancy.puerperium || '—' }}</td>
                                <td>{{ pregnancy.baby_age_at_death || pregnancy.baby_cause_of_death ? `${pregnancy.baby_age_at_death || ''} ${pregnancy.baby_cause_of_death || ''}` : '—' }}</td>
                            </tr>
                            <tr v-if="!previousPregnancies.length">
                                <td colspan="7" class="text-center text-muted py-3">No previous pregnancy details recorded.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card clinical-panel mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h5 mb-0">Ante-natal record</h2>
                        <p class="small text-muted mb-0">Clinical observations at each clinic visit.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Fundal height</th>
                                <th>Presentation / position</th>
                                <th>Presenting part to brim</th>
                                <th>Foetal heart</th>
                                <th>Urine</th>
                                <th>B.P.</th>
                                <th>Weight</th>
                                <th>PCV</th>
                                <th>Oedema</th>
                                <th>Remarks</th>
                                <th>Examiner</th>
                                <th>Return</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="visit in card.visits" :key="visit.id">
                                <td>{{ dateLabel(visit.visit_date) }}</td>
                                <td>{{ visit.fundal_height_cm ? `${visit.fundal_height_cm} cm` : '—' }}</td>
                                <td>{{ visit.presentation_position || '—' }}</td>
                                <td>{{ visit.presenting_part_relation || '—' }}</td>
                                <td>{{ visit.foetal_heart || '—' }}</td>
                                <td>{{ visit.urine || '—' }}</td>
                                <td>{{ visit.blood_pressure || '—' }}</td>
                                <td>{{ visit.weight_kg ? `${visit.weight_kg} kg` : '—' }}</td>
                                <td>{{ visit.pcv ? `${visit.pcv}%` : '—' }}</td>
                                <td>{{ visit.oedema || '—' }}</td>
                                <td>{{ visit.remarks || '—' }}</td>
                                <td>{{ visit.examiner_initials || '—' }}</td>
                                <td>{{ dateLabel(visit.return_date) }}</td>
                            </tr>
                            <tr v-if="!card.visits.length">
                                <td colspan="13" class="text-center text-muted py-3">No ANC visits recorded yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-if="canManage" class="card clinical-form-card">
                <div class="card-header"><h2 class="h5 mb-0">Record a visit</h2></div>
                <div class="card-body">
                    <form @submit.prevent="submitVisit">
                        <div class="row g-3">
                            <div class="col-sm-6 col-lg-3">
                                <label for="visit_date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input id="visit_date" v-model="form.visit_date" type="date" required class="form-control" :class="{ 'is-invalid': form.errors.visit_date }">
                                <div class="invalid-feedback">{{ form.errors.visit_date }}</div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="fundal_height_cm" class="form-label">Fundal height (cm)</label>
                                <input id="fundal_height_cm" v-model="form.fundal_height_cm" type="number" step="0.01" class="form-control" :class="{ 'is-invalid': form.errors.fundal_height_cm }">
                                <div class="invalid-feedback">{{ form.errors.fundal_height_cm }}</div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="presentation_position" class="form-label">Presentation / position</label>
                                <input id="presentation_position" v-model="form.presentation_position" class="form-control" :class="{ 'is-invalid': form.errors.presentation_position }">
                                <div class="invalid-feedback">{{ form.errors.presentation_position }}</div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="presenting_part_relation" class="form-label">Presenting part to brim</label>
                                <input id="presenting_part_relation" v-model="form.presenting_part_relation" class="form-control" :class="{ 'is-invalid': form.errors.presenting_part_relation }">
                                <div class="invalid-feedback">{{ form.errors.presenting_part_relation }}</div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="foetal_heart" class="form-label">Foetal heart</label>
                                <input id="foetal_heart" v-model="form.foetal_heart" class="form-control" :class="{ 'is-invalid': form.errors.foetal_heart }">
                                <div class="invalid-feedback">{{ form.errors.foetal_heart }}</div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="urine" class="form-label">Urine</label>
                                <input id="urine" v-model="form.urine" class="form-control" :class="{ 'is-invalid': form.errors.urine }">
                                <div class="invalid-feedback">{{ form.errors.urine }}</div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="blood_pressure" class="form-label">B.P.</label>
                                <input id="blood_pressure" v-model="form.blood_pressure" class="form-control" placeholder="e.g. 120/80" :class="{ 'is-invalid': form.errors.blood_pressure }">
                                <div class="invalid-feedback">{{ form.errors.blood_pressure }}</div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="weight_kg" class="form-label">Weight (kg)</label>
                                <input id="weight_kg" v-model="form.weight_kg" type="number" step="0.01" class="form-control" :class="{ 'is-invalid': form.errors.weight_kg }">
                                <div class="invalid-feedback">{{ form.errors.weight_kg }}</div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="pcv" class="form-label">PCV</label>
                                <input id="pcv" v-model="form.pcv" type="number" step="0.01" class="form-control" :class="{ 'is-invalid': form.errors.pcv }">
                                <div class="invalid-feedback">{{ form.errors.pcv }}</div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="oedema" class="form-label">Oedema</label>
                                <input id="oedema" v-model="form.oedema" class="form-control" :class="{ 'is-invalid': form.errors.oedema }">
                                <div class="invalid-feedback">{{ form.errors.oedema }}</div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="examiner_initials" class="form-label">Examiner initials</label>
                                <input id="examiner_initials" v-model="form.examiner_initials" class="form-control" :class="{ 'is-invalid': form.errors.examiner_initials }">
                                <div class="invalid-feedback">{{ form.errors.examiner_initials }}</div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="return_date" class="form-label">Return date</label>
                                <input id="return_date" v-model="form.return_date" type="date" class="form-control" :class="{ 'is-invalid': form.errors.return_date }">
                                <div class="invalid-feedback">{{ form.errors.return_date }}</div>
                            </div>
                            <div class="col-12">
                                <label for="remarks" class="form-label">Remarks</label>
                                <textarea id="remarks" v-model="form.remarks" rows="2" class="form-control" :class="{ 'is-invalid': form.errors.remarks }"></textarea>
                                <div class="invalid-feedback">{{ form.errors.remarks }}</div>
                            </div>
                        </div>
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                Save visit
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
