<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    patients: Array,
});

const form = useForm({
    patient_id: '',
    booking_date: '',
    pregnancy_duration_at_booking: '',
    age_at_booking: '',
    age_at_marriage: '',
    last_menstrual_period: '',
    expected_delivery_date: '',
    tribe: '',
    occupation: '',
    special_points: '',
    previous_pregnancies_total: '',
    living_children: '',
    is_ems_patient: false,
    medical_history: {
        heart_disease: false,
        chest_disease: false,
        kidney_disease: false,
        diabetes: false,
        twinning: false,
        operations: '',
        allergies: '',
        other: '',
    },
    previous_pregnancies: [],
    visits: [],
});

const selectedPatient = computed(() => props.patients.find((patient) => Number(patient.id) === Number(form.patient_id)));

const addPregnancy = () => form.previous_pregnancies.push({
    date_of_birth: '',
    duration: '',
    birth_weight: '',
    pregnancy_complications: '',
    labour_complications: '',
    puerperium: '',
    baby_age_at_death: '',
    baby_cause_of_death: '',
});

const addVisit = () => form.visits.push({
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

const submit = () => form.post(route('antenatal.store'));
</script>

<template>
    <AppLayout>
        <div class="clinical-page">
            <header class="clinical-page-heading">
                <div>
                    <div class="clinical-eyebrow">Maternal care</div>
                    <h1 class="clinical-page-title">Register ANC card</h1>
                    <p class="clinical-page-subtitle">Create the ante-natal card and record booking, pregnancy history, and clinic visits.</p>
                </div>
                <Link :href="route('patients.create')" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Registration options
                </Link>
            </header>

            <form @submit.prevent="submit">
                <section class="card clinical-form-card mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0"><i class="bi bi-person-vcard me-2"></i>ANC card details</h2>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                                <select id="patient_id" v-model="form.patient_id" class="form-select" :class="{ 'is-invalid': form.errors.patient_id }" required>
                                    <option value="" disabled>Select a registered patient...</option>
                                    <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                                        {{ patient.last_name }}, {{ patient.first_name }} — {{ patient.mrn }}
                                    </option>
                                </select>
                                <div class="invalid-feedback">{{ form.errors.patient_id }}</div>
                                <div v-if="!patients.length" class="form-text text-danger">Register the patient first before starting an ANC card.</div>
                            </div>
                            <div v-if="selectedPatient" class="col-12">
                                <div class="row g-2 rounded border bg-light p-3 mx-0">
                                    <div class="col-md-3">
                                        <span class="text-muted small d-block">Surname</span>
                                        <strong>{{ selectedPatient.last_name }}</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-muted small d-block">First name</span>
                                        <strong>{{ selectedPatient.first_name }}</strong>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="text-muted small d-block">I.P. number</span>
                                        <code>{{ selectedPatient.mrn }}</code>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="text-muted small d-block">Phone</span>
                                        {{ selectedPatient.phone || '—' }}
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-muted small d-block">Address</span>
                                        {{ selectedPatient.address || '—' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="booking_date" class="form-label">Date of booking</label>
                                <input id="booking_date" v-model="form.booking_date" type="date" class="form-control" :class="{ 'is-invalid': form.errors.booking_date }">
                                <div class="invalid-feedback">{{ form.errors.booking_date }}</div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="age_at_booking" class="form-label">Age at booking</label>
                                <input id="age_at_booking" v-model="form.age_at_booking" type="number" min="10" max="80" class="form-control" :class="{ 'is-invalid': form.errors.age_at_booking }">
                                <div class="invalid-feedback">{{ form.errors.age_at_booking }}</div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label for="pregnancy_duration_at_booking" class="form-label">Duration of pregnancy at booking</label>
                                <input id="pregnancy_duration_at_booking" v-model="form.pregnancy_duration_at_booking" class="form-control" placeholder="e.g. 12 weeks" :class="{ 'is-invalid': form.errors.pregnancy_duration_at_booking }">
                                <div class="invalid-feedback">{{ form.errors.pregnancy_duration_at_booking }}</div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label for="last_menstrual_period" class="form-label">L.M.P.</label>
                                <input id="last_menstrual_period" v-model="form.last_menstrual_period" type="date" class="form-control" :class="{ 'is-invalid': form.errors.last_menstrual_period }">
                                <div class="invalid-feedback">{{ form.errors.last_menstrual_period }}</div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label for="expected_delivery_date" class="form-label">E.D.D.</label>
                                <input id="expected_delivery_date" v-model="form.expected_delivery_date" type="date" class="form-control" :class="{ 'is-invalid': form.errors.expected_delivery_date }">
                                <div class="invalid-feedback">{{ form.errors.expected_delivery_date }}</div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label for="age_at_marriage" class="form-label">Age at marriage</label>
                                <input id="age_at_marriage" v-model="form.age_at_marriage" type="number" min="10" max="80" class="form-control" :class="{ 'is-invalid': form.errors.age_at_marriage }">
                                <div class="invalid-feedback">{{ form.errors.age_at_marriage }}</div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label for="tribe" class="form-label">Tribe</label>
                                <input id="tribe" v-model="form.tribe" class="form-control" :class="{ 'is-invalid': form.errors.tribe }">
                                <div class="invalid-feedback">{{ form.errors.tribe }}</div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label for="occupation" class="form-label">Occupation</label>
                                <input id="occupation" v-model="form.occupation" class="form-control" :class="{ 'is-invalid': form.errors.occupation }">
                                <div class="invalid-feedback">{{ form.errors.occupation }}</div>
                            </div>
                            <div class="col-md-6">
                                <label for="previous_pregnancies_total" class="form-label">Previous pregnancies, total</label>
                                <input id="previous_pregnancies_total" v-model="form.previous_pregnancies_total" type="number" min="0" max="50" class="form-control" :class="{ 'is-invalid': form.errors.previous_pregnancies_total }">
                                <div class="invalid-feedback">{{ form.errors.previous_pregnancies_total }}</div>
                            </div>
                            <div class="col-md-6">
                                <label for="living_children" class="form-label">Number of living children</label>
                                <input id="living_children" v-model="form.living_children" type="number" min="0" max="50" class="form-control" :class="{ 'is-invalid': form.errors.living_children }">
                                <div class="invalid-feedback">{{ form.errors.living_children }}</div>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input id="is_ems_patient" v-model="form.is_ems_patient" type="checkbox" class="form-check-input">
                                    <label for="is_ems_patient" class="form-check-label">Flag for EMS support</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="special_points" class="form-label">Special points</label>
                                <textarea id="special_points" v-model="form.special_points" rows="2" class="form-control" :class="{ 'is-invalid': form.errors.special_points }"></textarea>
                                <div class="invalid-feedback">{{ form.errors.special_points }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card clinical-form-card mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Previous medical history</h2>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div v-for="condition in [
                                ['heart_disease', 'Heart disease'],
                                ['chest_disease', 'Chest disease'],
                                ['kidney_disease', 'Kidney disease'],
                                ['diabetes', 'Diabetes'],
                                ['twinning', 'History of twinning'],
                            ]" :key="condition[0]" class="col-sm-6 col-lg-4">
                                <div class="form-check">
                                    <input :id="`history-${condition[0]}`" v-model="form.medical_history[condition[0]]" type="checkbox" class="form-check-input">
                                    <label :for="`history-${condition[0]}`" class="form-check-label">{{ condition[1] }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="history_operations" class="form-label">Operations</label>
                                <textarea id="history_operations" v-model="form.medical_history.operations" rows="2" class="form-control" :class="{ 'is-invalid': form.errors['medical_history.operations'] }"></textarea>
                                <div class="invalid-feedback">{{ form.errors['medical_history.operations'] }}</div>
                            </div>
                            <div class="col-md-6">
                                <label for="history_allergies" class="form-label">Allergies</label>
                                <textarea id="history_allergies" v-model="form.medical_history.allergies" rows="2" class="form-control" :class="{ 'is-invalid': form.errors['medical_history.allergies'] }"></textarea>
                                <div class="invalid-feedback">{{ form.errors['medical_history.allergies'] }}</div>
                            </div>
                            <div class="col-12">
                                <label for="history_other" class="form-label">Other history</label>
                                <textarea id="history_other" v-model="form.medical_history.other" rows="2" class="form-control" :class="{ 'is-invalid': form.errors['medical_history.other'] }"></textarea>
                                <div class="invalid-feedback">{{ form.errors['medical_history.other'] }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card clinical-form-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h5 mb-0">Previous pregnancies</h2>
                            <p class="small text-muted mb-0">Birth details and any complications, including baby age/cause of death if applicable.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary flex-shrink-0" @click="addPregnancy">
                            <i class="bi bi-plus-lg me-1"></i> Add pregnancy
                        </button>
                    </div>
                    <div class="card-body">
                        <p v-if="!form.previous_pregnancies.length" class="text-muted mb-0">No previous pregnancy details added.</p>
                        <div v-for="(pregnancy, index) in form.previous_pregnancies" :key="index" class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="h6 mb-0">Pregnancy {{ index + 1 }}</h3>
                                <button type="button" class="btn btn-sm btn-outline-danger" @click="form.previous_pregnancies.splice(index, 1)">Remove</button>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Date of birth</label>
                                    <input v-model="pregnancy.date_of_birth" type="date" class="form-control" :class="{ 'is-invalid': form.errors[`previous_pregnancies.${index}.date_of_birth`] }">
                                    <div class="invalid-feedback">{{ form.errors[`previous_pregnancies.${index}.date_of_birth`] }}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Duration of pregnancy</label>
                                    <input v-model="pregnancy.duration" class="form-control" :class="{ 'is-invalid': form.errors[`previous_pregnancies.${index}.duration`] }">
                                    <div class="invalid-feedback">{{ form.errors[`previous_pregnancies.${index}.duration`] }}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Birth weight (kg)</label>
                                    <input v-model="pregnancy.birth_weight" type="number" step="0.01" min="0" max="10" class="form-control" :class="{ 'is-invalid': form.errors[`previous_pregnancies.${index}.birth_weight`] }">
                                    <div class="invalid-feedback">{{ form.errors[`previous_pregnancies.${index}.birth_weight`] }}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Baby age at death</label>
                                    <input v-model="pregnancy.baby_age_at_death" class="form-control" :class="{ 'is-invalid': form.errors[`previous_pregnancies.${index}.baby_age_at_death`] }">
                                    <div class="invalid-feedback">{{ form.errors[`previous_pregnancies.${index}.baby_age_at_death`] }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Complications in pregnancy</label>
                                    <textarea v-model="pregnancy.pregnancy_complications" rows="2" class="form-control" :class="{ 'is-invalid': form.errors[`previous_pregnancies.${index}.pregnancy_complications`] }"></textarea>
                                    <div class="invalid-feedback">{{ form.errors[`previous_pregnancies.${index}.pregnancy_complications`] }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Complications in labour</label>
                                    <textarea v-model="pregnancy.labour_complications" rows="2" class="form-control" :class="{ 'is-invalid': form.errors[`previous_pregnancies.${index}.labour_complications`] }"></textarea>
                                    <div class="invalid-feedback">{{ form.errors[`previous_pregnancies.${index}.labour_complications`] }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Puerperium</label>
                                    <textarea v-model="pregnancy.puerperium" rows="2" class="form-control" :class="{ 'is-invalid': form.errors[`previous_pregnancies.${index}.puerperium`] }"></textarea>
                                    <div class="invalid-feedback">{{ form.errors[`previous_pregnancies.${index}.puerperium`] }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Cause of death (if applicable)</label>
                                    <textarea v-model="pregnancy.baby_cause_of_death" rows="2" class="form-control" :class="{ 'is-invalid': form.errors[`previous_pregnancies.${index}.baby_cause_of_death`] }"></textarea>
                                    <div class="invalid-feedback">{{ form.errors[`previous_pregnancies.${index}.baby_cause_of_death`] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card clinical-form-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h5 mb-0">Ante-natal record</h2>
                            <p class="small text-muted mb-0">Add any visits recorded when opening the card. More can be added later.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary flex-shrink-0" @click="addVisit">
                            <i class="bi bi-plus-lg me-1"></i> Add visit
                        </button>
                    </div>
                    <div class="card-body">
                        <p v-if="!form.visits.length" class="text-muted mb-0">No visit rows added yet.</p>
                        <div v-for="(visit, index) in form.visits" :key="index" class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="h6 mb-0">Visit {{ index + 1 }}</h3>
                                <button type="button" class="btn btn-sm btn-outline-danger" @click="form.visits.splice(index, 1)">Remove</button>
                            </div>
                            <div class="row g-3">
                                <div class="col-sm-6 col-lg-3">
                                    <label class="form-label">Date</label>
                                    <input v-model="visit.visit_date" type="date" class="form-control" :class="{ 'is-invalid': form.errors[`visits.${index}.visit_date`] }">
                                    <div class="invalid-feedback">{{ form.errors[`visits.${index}.visit_date`] }}</div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <label class="form-label">Fundal height (cm)</label>
                                    <input v-model="visit.fundal_height_cm" type="number" step="0.01" class="form-control" :class="{ 'is-invalid': form.errors[`visits.${index}.fundal_height_cm`] }">
                                    <div class="invalid-feedback">{{ form.errors[`visits.${index}.fundal_height_cm`] }}</div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <label class="form-label">Presentation / position</label>
                                    <input v-model="visit.presentation_position" class="form-control" :class="{ 'is-invalid': form.errors[`visits.${index}.presentation_position`] }">
                                    <div class="invalid-feedback">{{ form.errors[`visits.${index}.presentation_position`] }}</div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <label class="form-label">Presenting part to brim</label>
                                    <input v-model="visit.presenting_part_relation" class="form-control" :class="{ 'is-invalid': form.errors[`visits.${index}.presenting_part_relation`] }">
                                    <div class="invalid-feedback">{{ form.errors[`visits.${index}.presenting_part_relation`] }}</div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <label class="form-label">Foetal heart</label>
                                    <input v-model="visit.foetal_heart" class="form-control" :class="{ 'is-invalid': form.errors[`visits.${index}.foetal_heart`] }">
                                    <div class="invalid-feedback">{{ form.errors[`visits.${index}.foetal_heart`] }}</div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <label class="form-label">Urine</label>
                                    <input v-model="visit.urine" class="form-control" :class="{ 'is-invalid': form.errors[`visits.${index}.urine`] }">
                                    <div class="invalid-feedback">{{ form.errors[`visits.${index}.urine`] }}</div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <label class="form-label">B.P.</label>
                                    <input v-model="visit.blood_pressure" class="form-control" placeholder="e.g. 120/80" :class="{ 'is-invalid': form.errors[`visits.${index}.blood_pressure`] }">
                                    <div class="invalid-feedback">{{ form.errors[`visits.${index}.blood_pressure`] }}</div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <label class="form-label">Weight (kg)</label>
                                    <input v-model="visit.weight_kg" type="number" step="0.01" class="form-control" :class="{ 'is-invalid': form.errors[`visits.${index}.weight_kg`] }">
                                    <div class="invalid-feedback">{{ form.errors[`visits.${index}.weight_kg`] }}</div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <label class="form-label">PCV</label>
                                    <input v-model="visit.pcv" type="number" step="0.01" class="form-control" :class="{ 'is-invalid': form.errors[`visits.${index}.pcv`] }">
                                    <div class="invalid-feedback">{{ form.errors[`visits.${index}.pcv`] }}</div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <label class="form-label">Oedema</label>
                                    <input v-model="visit.oedema" class="form-control" :class="{ 'is-invalid': form.errors[`visits.${index}.oedema`] }">
                                    <div class="invalid-feedback">{{ form.errors[`visits.${index}.oedema`] }}</div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <label class="form-label">Examiner initials</label>
                                    <input v-model="visit.examiner_initials" class="form-control" :class="{ 'is-invalid': form.errors[`visits.${index}.examiner_initials`] }">
                                    <div class="invalid-feedback">{{ form.errors[`visits.${index}.examiner_initials`] }}</div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <label class="form-label">Return date</label>
                                    <input v-model="visit.return_date" type="date" class="form-control" :class="{ 'is-invalid': form.errors[`visits.${index}.return_date`] }">
                                    <div class="invalid-feedback">{{ form.errors[`visits.${index}.return_date`] }}</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Remarks</label>
                                    <textarea v-model="visit.remarks" rows="2" class="form-control" :class="{ 'is-invalid': form.errors[`visits.${index}.remarks`] }"></textarea>
                                    <div class="invalid-feedback">{{ form.errors[`visits.${index}.remarks`] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="d-flex justify-content-end gap-2 mb-4">
                    <Link :href="route('antenatal.index')" class="btn btn-outline-secondary">Cancel</Link>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing || !patients.length">
                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        Save ANC card
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
