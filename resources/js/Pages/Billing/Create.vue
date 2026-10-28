<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    patients: Array,
    currency: String,
});

const form = useForm({
    patient_id: '',
    issued_at: new Date().toISOString().slice(0, 10),
    due_at: '',
    discount: '0.00',
    notes: '',
    items: [{ description: '', quantity: 1, unit_price: '0.00' }],
});

const subtotal = computed(() => form.items.reduce(
    (total, item) => total + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0),
    0,
));
const total = computed(() => Math.max(0, subtotal.value - (Number(form.discount) || 0)));
const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: props.currency || 'USD',
});
const money = (amount) => currencyFormat.format(amount);

const addItem = () => form.items.push({ description: '', quantity: 1, unit_price: '0.00' });
const removeItem = (index) => {
    if (form.items.length > 1) form.items.splice(index, 1);
};
const submit = () => form.post(route('billing.store'));
</script>

<template>
    <AppLayout>
        <div class="clinical-page">
        <div class="clinical-page-heading">
            <div>
                <div class="clinical-eyebrow">Revenue management</div>
                <h1 class="clinical-page-title">Create an invoice</h1>
                <p class="clinical-page-subtitle">Add billable items and payment details for a registered patient.</p>
            </div>
            <Link :href="route('billing.index')" class="btn btn-outline-secondary">Back to billing</Link>
        </div>

        <form @submit.prevent="submit">
            <div class="card clinical-panel mb-4">
                <div class="card-header"><h2>Invoice details</h2></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="patient" class="form-label">Patient <span class="text-danger">*</span></label>
                            <select id="patient" v-model="form.patient_id" class="form-select" :class="{ 'is-invalid': form.errors.patient_id }">
                                <option value="" disabled>Select a patient</option>
                                <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                                    {{ patient.last_name }}, {{ patient.first_name }} · {{ patient.mrn }}
                                </option>
                            </select>
                            <div class="invalid-feedback">{{ form.errors.patient_id }}</div>
                            <div v-if="patients.length === 0" class="form-text text-danger">Register a patient before creating an invoice.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="issued-at" class="form-label">Issue date <span class="text-danger">*</span></label>
                            <input id="issued-at" v-model="form.issued_at" type="date" class="form-control" :class="{ 'is-invalid': form.errors.issued_at }">
                            <div class="invalid-feedback">{{ form.errors.issued_at }}</div>
                        </div>
                        <div class="col-md-3">
                            <label for="due-at" class="form-label">Due date</label>
                            <input id="due-at" v-model="form.due_at" type="date" :min="form.issued_at" class="form-control" :class="{ 'is-invalid': form.errors.due_at }">
                            <div class="invalid-feedback">{{ form.errors.due_at }}</div>
                        </div>
                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" v-model="form.notes" rows="2" class="form-control" :class="{ 'is-invalid': form.errors.notes }" />
                            <div class="invalid-feedback">{{ form.errors.notes }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card clinical-panel mb-4">
                <div class="card-header">
                    <h2>Invoice items</h2>
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addItem">
                        <i class="bi bi-plus-lg me-1"></i> Add item
                    </button>
                </div>
                <div class="card-body">
                    <div v-for="(item, index) in form.items" :key="index" class="row g-2 align-items-start mb-3">
                        <div class="col-md-6">
                            <label :for="`description-${index}`" class="form-label">Description</label>
                            <input :id="`description-${index}`" v-model="item.description" class="form-control" :class="{ 'is-invalid': form.errors[`items.${index}.description`] }" placeholder="Consultation, laboratory test, etc.">
                            <div class="invalid-feedback">{{ form.errors[`items.${index}.description`] }}</div>
                        </div>
                        <div class="col-6 col-md-2">
                            <label :for="`quantity-${index}`" class="form-label">Quantity</label>
                            <input :id="`quantity-${index}`" v-model.number="item.quantity" type="number" min="1" max="100000" class="form-control" :class="{ 'is-invalid': form.errors[`items.${index}.quantity`] }">
                            <div class="invalid-feedback">{{ form.errors[`items.${index}.quantity`] }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label :for="`price-${index}`" class="form-label">Unit price ({{ currency }})</label>
                            <input :id="`price-${index}`" v-model="item.unit_price" type="number" min="0.01" step="0.01" class="form-control" :class="{ 'is-invalid': form.errors[`items.${index}.unit_price`] }">
                            <div class="invalid-feedback">{{ form.errors[`items.${index}.unit_price`] }}</div>
                        </div>
                        <div class="col-md-1 d-flex justify-content-end pt-md-4">
                            <button type="button" class="btn btn-outline-danger" :disabled="form.items.length === 1" :aria-label="`Remove item ${index + 1}`" @click="removeItem(index)">
                                <i class="bi bi-trash" />
                            </button>
                        </div>
                    </div>
                    <div v-if="form.errors.items" class="text-danger small">{{ form.errors.items }}</div>
                    <div class="row justify-content-end">
                        <div class="col-md-5 col-lg-4">
                            <div class="d-flex justify-content-between border-top pt-3 mb-2">
                                <span>Subtotal</span><strong>{{ money(subtotal) }}</strong>
                            </div>
                            <div class="mb-3">
                                <label for="discount" class="form-label">Discount ({{ currency }})</label>
                                <input id="discount" v-model="form.discount" type="number" min="0" step="0.01" class="form-control" :class="{ 'is-invalid': form.errors.discount }">
                                <div class="invalid-feedback">{{ form.errors.discount }}</div>
                            </div>
                            <div class="invoice-total d-flex justify-content-between">
                                <span>Total</span><strong>{{ money(total) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-4">
                <Link :href="route('billing.index')" class="btn btn-outline-secondary">Cancel</Link>
                <button type="submit" class="btn btn-primary" :disabled="form.processing || patients.length === 0">
                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
                    Create invoice
                </button>
            </div>
        </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.invoice-total {
    align-items: center;
    background: linear-gradient(110deg, #edf6fa, #eef9f5);
    border-radius: 0.65rem;
    color: #176b87;
    font-size: 1.05rem;
    font-weight: 600;
    padding: 0.85rem 0.9rem;
}

.invoice-total strong {
    color: #203047;
    font-size: 1.2rem;
}
</style>
