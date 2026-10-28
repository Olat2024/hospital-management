<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    medicines: Object,
    prescriptions: Object,
    filters: Object,
    summary: Object,
    canManage: Boolean,
});

const search = ref(props.filters.search);
const activePrescription = ref(null);
const medicineForm = useForm({
    name: '',
    generic_name: '',
    strength: '',
    dosage_form: '',
    initial_stock: 0,
    reorder_level: 10,
});
const stockForm = useForm({ medicine_id: '', quantity: '', reference: '' });
const stockDispenseForm = useForm({ quantity: '', reference: '' });
const stockDispenseMedicine = ref(null);
const dispenseForm = useForm({
    notes: '',
    items: [{ medicine_id: '', quantity: 1 }],
});
const selectedMedicine = computed(() =>
    props.medicines.data.find((medicine) => medicine.id === Number(stockForm.medicine_id))
);
const selectedStockDispenseMedicine = computed(() =>
    props.medicines.data.find((medicine) => medicine.id === stockDispenseMedicine.value)
);
const medicineStockFor = (medicineId) =>
    props.medicines.data.find((medicine) => medicine.id === Number(medicineId));
const remainingAfterDispense = (index) => {
    const item = dispenseForm.items[index];
    const medicine = medicineStockFor(item.medicine_id);

    return medicine
        ? Math.max(0, medicine.stock_quantity - (Number(item.quantity) || 0))
        : null;
};
const stockLevelPercent = (medicine) => medicine.reorder_level > 0
    ? Math.min(100, Math.round((medicine.stock_quantity / medicine.reorder_level) * 100))
    : medicine.stock_quantity > 0 ? 100 : 0;

const applySearch = () => router.get(route('pharmacy.index'), {
    search: search.value || undefined,
}, { preserveState: true, replace: true });

const addMedicine = () => medicineForm.post(route('pharmacy.medicines.store'), {
    preserveScroll: true,
    onSuccess: () => medicineForm.reset(),
});

const openStock = (medicine) => {
    stockForm.reset();
    stockForm.medicine_id = medicine.id;
};

const receiveStock = () => stockForm.post(route('pharmacy.stock.store', stockForm.medicine_id), {
    preserveScroll: true,
    onSuccess: () => stockForm.reset(),
});

const openStockDispense = (medicine) => {
    stockDispenseForm.reset();
    stockDispenseMedicine.value = medicine.id;
};

const recordStockDispense = () => stockDispenseForm.post(
    route('pharmacy.stock.dispense', stockDispenseMedicine.value),
    {
        preserveScroll: true,
        onSuccess: () => {
            stockDispenseMedicine.value = null;
            stockDispenseForm.reset();
        },
    }
);

const openDispense = (prescription) => {
    activePrescription.value = activePrescription.value === prescription.id ? null : prescription.id;
    dispenseForm.reset();
    dispenseForm.items = [{ medicine_id: '', quantity: 1 }];
};

const addDispenseItem = () => dispenseForm.items.push({ medicine_id: '', quantity: 1 });
const removeDispenseItem = (index) => {
    if (dispenseForm.items.length > 1) dispenseForm.items.splice(index, 1);
};

const dispense = (recordId) => dispenseForm.post(route('pharmacy.dispense', recordId), {
    preserveScroll: true,
    onSuccess: () => {
        activePrescription.value = null;
        dispenseForm.reset();
    },
});

const medicineLabel = (medicine) =>
    [medicine.name, medicine.strength, medicine.dosage_form].filter(Boolean).join(' · ');

const analyticsCards = computed(() => [
    {
        label: 'Active medicines',
        value: props.summary.medicineCount,
        detail: 'Products in the active catalogue',
        icon: 'bi-capsule',
        tone: 'pharmacy-card-blue',
    },
    {
        label: 'Units in stock',
        value: props.summary.unitsInStock.toLocaleString(),
        detail: 'Available across active medicines',
        icon: 'bi-box-seam',
        tone: 'pharmacy-card-teal',
    },
    {
        label: 'Low-stock items',
        value: props.summary.lowStockCount,
        detail: 'At or below the reorder level',
        icon: 'bi-exclamation-triangle',
        tone: props.summary.lowStockCount > 0 ? 'pharmacy-card-amber' : 'pharmacy-card-green',
    },
    {
        label: 'Prescriptions on file',
        value: props.summary.prescriptionCount,
        detail: 'Clinical prescriptions recorded',
        icon: 'bi-prescription2',
        tone: 'pharmacy-card-violet',
    },
]);
</script>

<template>
    <AppLayout>
        <div class="app-page-header">
            <div class="app-page-header-content">
                <div class="app-page-eyebrow">Medication services</div>
                <h1 class="app-page-title">Pharmacy</h1>
                <p class="app-page-description">Manage medicine stock and dispense against clinical prescriptions.</p>
            </div>
            <form class="app-page-header-actions" @submit.prevent="applySearch">
                <label for="pharmacy-search" class="visually-hidden">Search pharmacy</label>
                <input id="pharmacy-search" v-model="search" class="form-control" placeholder="Search medicines or patients">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search" /></button>
            </form>
        </div>

        <section class="row g-3 mb-4 pharmacy-analytics" aria-label="Pharmacy analytics">
            <div v-for="card in analyticsCards" :key="card.label" class="col-sm-6 col-xl-3">
                <article class="pharmacy-analytics-card h-100" :class="card.tone">
                    <div class="pharmacy-analytics-top">
                        <span class="pharmacy-analytics-icon"><i class="bi" :class="card.icon"></i></span>
                        <span class="pharmacy-analytics-context">Inventory</span>
                    </div>
                    <div class="pharmacy-analytics-label">{{ card.label }}</div>
                    <div class="pharmacy-analytics-value">{{ card.value }}</div>
                    <div class="pharmacy-analytics-detail">{{ card.detail }}</div>
                    <div class="pharmacy-analytics-accent" aria-hidden="true"></div>
                </article>
            </div>
        </section>

        <div v-if="canManage" class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3"><h5 class="mb-0">Add medicine to inventory</h5></div>
            <div class="card-body">
                <form @submit.prevent="addMedicine">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="medicine-name" class="form-label">Brand / medicine name</label>
                            <input id="medicine-name" v-model="medicineForm.name" class="form-control" :class="{ 'is-invalid': medicineForm.errors.name }" required>
                            <div class="invalid-feedback">{{ medicineForm.errors.name }}</div>
                        </div>
                        <div class="col-md-4">
                            <label for="medicine-generic" class="form-label">Generic name</label>
                            <input id="medicine-generic" v-model="medicineForm.generic_name" class="form-control" :class="{ 'is-invalid': medicineForm.errors.generic_name }">
                            <div class="invalid-feedback">{{ medicineForm.errors.generic_name }}</div>
                        </div>
                        <div class="col-sm-6 col-md-2">
                            <label for="medicine-strength" class="form-label">Strength</label>
                            <input id="medicine-strength" v-model="medicineForm.strength" class="form-control" :class="{ 'is-invalid': medicineForm.errors.strength }" placeholder="e.g. 250 mg">
                            <div class="invalid-feedback">{{ medicineForm.errors.strength }}</div>
                        </div>
                        <div class="col-sm-6 col-md-2">
                            <label for="medicine-form" class="form-label">Dosage form</label>
                            <input id="medicine-form" v-model="medicineForm.dosage_form" class="form-control" :class="{ 'is-invalid': medicineForm.errors.dosage_form }" placeholder="Tablet">
                            <div class="invalid-feedback">{{ medicineForm.errors.dosage_form }}</div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label for="medicine-initial-stock" class="form-label">Opening stock (units)</label>
                            <input id="medicine-initial-stock" v-model="medicineForm.initial_stock" type="number" min="0" class="form-control" :class="{ 'is-invalid': medicineForm.errors.initial_stock }" required>
                            <div class="invalid-feedback">{{ medicineForm.errors.initial_stock }}</div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label for="medicine-reorder-level" class="form-label">Reorder level (units)</label>
                            <input id="medicine-reorder-level" v-model="medicineForm.reorder_level" type="number" min="0" class="form-control" :class="{ 'is-invalid': medicineForm.errors.reorder_level }" required>
                            <div class="invalid-feedback">{{ medicineForm.errors.reorder_level }}</div>
                        </div>
                        <div class="col-md-6 d-grid">
                            <button type="submit" class="btn btn-primary" :disabled="medicineForm.processing">
                                <span v-if="medicineForm.processing" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
                                Add medicine
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0">Medicine inventory</h5>
                <span class="small text-muted">{{ medicines.total }} items</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Medicine</th><th>Generic name</th><th>Dosage form</th><th>Available stock</th><th>Reorder at</th><th>Status</th><th v-if="canManage" class="text-end">Action</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="medicine in medicines.data" :key="medicine.id">
                            <td class="fw-semibold">{{ medicine.name }}<div v-if="medicine.strength" class="small text-muted">{{ medicine.strength }}</div></td>
                            <td>{{ medicine.generic_name || '—' }}</td>
                            <td>{{ medicine.dosage_form || '—' }}</td>
                            <td>
                                <div class="stock-quantity">{{ medicine.stock_quantity }} <span>units</span></div>
                                <div class="stock-meter" role="progressbar" :aria-valuenow="medicine.stock_quantity" aria-valuemin="0" :aria-valuemax="Math.max(medicine.reorder_level, 1)" :aria-label="`${medicine.name} stock level`">
                                    <span :class="medicine.stock_quantity <= medicine.reorder_level ? 'stock-meter-low' : 'stock-meter-ok'" :style="{ width: `${stockLevelPercent(medicine)}%` }" />
                                </div>
                                <div class="stock-caption">Remaining in inventory</div>
                            </td>
                            <td>{{ medicine.reorder_level }}</td>
                            <td>
                                <span v-if="medicine.stock_quantity <= medicine.reorder_level" class="badge bg-warning text-dark">Low stock</span>
                                <span v-else class="badge bg-success">In stock</span>
                            </td>
                            <td v-if="canManage" class="text-end">
                                <div class="d-flex flex-wrap justify-content-end gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="openStock(medicine)">
                                        <i class="bi bi-box-seam me-1"></i> Receive stock
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" :disabled="medicine.stock_quantity < 1" @click="openStockDispense(medicine)">
                                        <i class="bi bi-capsule me-1"></i> Dispense
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="medicines.data.length === 0">
                            <td :colspan="canManage ? 7 : 6" class="text-center py-4 text-muted">No medicines found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="medicines.prev_page_url || medicines.next_page_url" class="card-footer bg-white d-flex justify-content-between">
                <Link v-if="medicines.prev_page_url" :href="medicines.prev_page_url" class="btn btn-sm btn-outline-secondary">Previous medicines</Link>
                <span v-else />
                <Link v-if="medicines.next_page_url" :href="medicines.next_page_url" class="btn btn-sm btn-outline-secondary">More medicines</Link>
            </div>
        </div>

        <div v-if="canManage && stockDispenseMedicine" class="card shadow-sm mb-4 border-danger">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0">Record dispensing: {{ selectedStockDispenseMedicine?.name }}</h5>
                <button type="button" class="btn-close" aria-label="Close" @click="stockDispenseMedicine = null; stockDispenseForm.reset()" />
            </div>
            <div class="card-body">
                <form class="row g-3 align-items-end" @submit.prevent="recordStockDispense">
                    <div class="col-md-3">
                        <label for="stock-dispense-quantity" class="form-label">Quantity dispensed</label>
                        <input id="stock-dispense-quantity" v-model="stockDispenseForm.quantity" type="number" min="1" :max="selectedStockDispenseMedicine?.stock_quantity" class="form-control" :class="{ 'is-invalid': stockDispenseForm.errors.quantity }" required>
                        <div class="invalid-feedback">{{ stockDispenseForm.errors.quantity }}</div>
                    </div>
                    <div class="col-md-6">
                        <label for="stock-dispense-reference" class="form-label">Patient / reference (optional)</label>
                        <input id="stock-dispense-reference" v-model="stockDispenseForm.reference" class="form-control" :class="{ 'is-invalid': stockDispenseForm.errors.reference }" placeholder="Patient or dispensing reference">
                        <div class="invalid-feedback">{{ stockDispenseForm.errors.reference }}</div>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-danger" :disabled="stockDispenseForm.processing">
                            <span v-if="stockDispenseForm.processing" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
                            Confirm dispensing
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="canManage && stockForm.medicine_id" class="card shadow-sm mb-4 border-primary">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0">Receive stock: {{ selectedMedicine?.name }}</h5>
                <button type="button" class="btn-close" aria-label="Close" @click="stockForm.reset()" />
            </div>
            <div class="card-body">
                <form class="row g-3 align-items-end" @submit.prevent="receiveStock">
                    <div class="col-md-3">
                        <label for="stock-quantity" class="form-label">Quantity received</label>
                        <input id="stock-quantity" v-model="stockForm.quantity" type="number" min="1" class="form-control" :class="{ 'is-invalid': stockForm.errors.quantity }" required>
                        <div class="invalid-feedback">{{ stockForm.errors.quantity }}</div>
                    </div>
                    <div class="col-md-6">
                        <label for="stock-reference" class="form-label">Supplier / reference</label>
                        <input id="stock-reference" v-model="stockForm.reference" class="form-control" :class="{ 'is-invalid': stockForm.errors.reference }">
                        <div class="invalid-feedback">{{ stockForm.errors.reference }}</div>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-primary" :disabled="stockForm.processing">Record stock receipt</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <div>
                    <h5 class="mb-0">Prescriptions</h5>
                    <p class="small text-muted mb-0">Confirm medicine, strength, form, and quantity against the doctor's prescription before dispensing.</p>
                </div>
                <span class="small text-muted">{{ prescriptions.total }} records</span>
            </div>
            <div class="list-group list-group-flush">
                <div v-for="prescription in prescriptions.data" :key="prescription.id" class="list-group-item p-3">
                    <div class="d-flex flex-wrap justify-content-between gap-3">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">
                                {{ prescription.patient.first_name }} {{ prescription.patient.last_name }}
                                <span class="small text-muted ms-1">{{ prescription.patient.mrn }}</span>
                            </div>
                            <div class="small text-muted">
                                {{ prescription.created_at }} · Dr. {{ prescription.doctor?.first_name }} {{ prescription.doctor?.last_name }}
                            </div>
                            <div class="mt-2"><strong>Prescription:</strong> {{ prescription.prescription }}</div>
                            <div v-if="prescription.dispensations.length" class="mt-3 small">
                                <div class="text-muted fw-semibold mb-1">Dispensing history</div>
                                <div v-for="dispensation in prescription.dispensations" :key="dispensation.id" class="border-start border-success border-3 ps-2 mb-2">
                                    <div>{{ new Date(dispensation.dispensed_at).toLocaleString() }} · {{ dispensation.dispensed_by?.name || 'Staff' }}</div>
                                    <div v-for="item in dispensation.items" :key="item.id">
                                        {{ item.quantity }} × {{ medicineLabel(item.medicine) }}
                                    </div>
                                    <div v-if="dispensation.notes" class="text-muted">{{ dispensation.notes }}</div>
                                </div>
                            </div>
                        </div>
                        <div v-if="canManage" class="align-self-start">
                            <button type="button" class="btn btn-sm" :class="activePrescription === prescription.id ? 'btn-outline-secondary' : 'btn-success'" @click="openDispense(prescription)">
                                {{ activePrescription === prescription.id ? 'Close' : 'Record dispensing' }}
                            </button>
                        </div>
                    </div>
                    <form v-if="canManage && activePrescription === prescription.id" class="border-top mt-3 pt-3" @submit.prevent="dispense(prescription.id)">
                        <div v-for="(item, index) in dispenseForm.items" :key="index" class="row g-2 align-items-start mb-2">
                            <div class="col-md-8">
                                <label :for="`dispense-medicine-${index}`" class="form-label">Medicine</label>
                                <select :id="`dispense-medicine-${index}`" v-model="item.medicine_id" class="form-select" :class="{ 'is-invalid': dispenseForm.errors[`items.${index}.medicine_id`] }" required>
                                    <option value="" disabled>Select medicine in stock</option>
                                    <option v-for="medicine in medicines.data.filter((entry) => entry.stock_quantity > 0)" :key="medicine.id" :value="medicine.id" :disabled="dispenseForm.items.some((other, otherIndex) => otherIndex !== index && Number(other.medicine_id) === medicine.id)">
                                        {{ medicineLabel(medicine) }} — {{ medicine.stock_quantity }} in stock
                                    </option>
                                </select>
                                <div class="invalid-feedback">{{ dispenseForm.errors[`items.${index}.medicine_id`] }}</div>
                            </div>
                            <div class="col-8 col-md-3">
                                <label :for="`dispense-quantity-${index}`" class="form-label">Units dispensed</label>
                                <input :id="`dispense-quantity-${index}`" v-model="item.quantity" type="number" min="1" :max="medicineStockFor(item.medicine_id)?.stock_quantity" class="form-control" :class="{ 'is-invalid': dispenseForm.errors[`items.${index}.quantity`] }" required>
                                <div class="invalid-feedback">{{ dispenseForm.errors[`items.${index}.quantity`] }}</div>
                            </div>
                            <div class="col-4 col-md-1 d-flex justify-content-end pt-md-4">
                                <button type="button" class="btn btn-outline-danger" :disabled="dispenseForm.items.length === 1" :aria-label="`Remove medicine ${index + 1}`" @click="removeDispenseItem(index)">
                                    <i class="bi bi-trash" />
                                </button>
                            </div>
                            <div v-if="medicineStockFor(item.medicine_id)" class="col-12">
                                <div class="dispense-stock-monitor" :class="{ 'dispense-stock-empty': remainingAfterDispense(index) === 0 }">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <span><i class="bi bi-box-seam me-1"></i> Current stock <strong>{{ medicineStockFor(item.medicine_id).stock_quantity }} units</strong></span>
                                        <span>Remaining after dispensing <strong>{{ remainingAfterDispense(index) }} units</strong></span>
                                    </div>
                                    <div class="dispense-stock-track" role="progressbar" :aria-valuenow="remainingAfterDispense(index)" aria-valuemin="0" :aria-valuemax="medicineStockFor(item.medicine_id).stock_quantity" :aria-label="`Remaining ${medicineLabel(medicineStockFor(item.medicine_id))} stock after dispense`">
                                        <span :style="{ width: `${medicineStockFor(item.medicine_id).stock_quantity ? remainingAfterDispense(index) / medicineStockFor(item.medicine_id).stock_quantity * 100 : 0}%` }" />
                                    </div>
                                    <small v-if="remainingAfterDispense(index) === 0" class="text-danger">This dispense will use all available stock.</small>
                                </div>
                            </div>
                        </div>
                        <div v-if="dispenseForm.errors.items" class="text-danger small mb-2">{{ dispenseForm.errors.items }}</div>
                        <button type="button" class="btn btn-sm btn-outline-primary mb-3" @click="addDispenseItem">
                            <i class="bi bi-plus-lg me-1"></i> Add another medicine
                        </button>
                        <div class="mb-3">
                            <label :for="`dispense-notes-${prescription.id}`" class="form-label">Dispensing notes</label>
                            <input :id="`dispense-notes-${prescription.id}`" v-model="dispenseForm.notes" class="form-control" :class="{ 'is-invalid': dispenseForm.errors.notes }" placeholder="Optional notes">
                            <div class="invalid-feedback">{{ dispenseForm.errors.notes }}</div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success" :disabled="dispenseForm.processing || medicines.data.every((medicine) => medicine.stock_quantity === 0)">
                                <span v-if="dispenseForm.processing" class="spinner-border spinner-border-sm me-1" aria-hidden="true" />
                                Confirm dispensing
                            </button>
                        </div>
                    </form>
                </div>
                <div v-if="prescriptions.data.length === 0" class="text-center py-5 text-muted">
                    No prescriptions found.
                </div>
            </div>
            <div v-if="prescriptions.prev_page_url || prescriptions.next_page_url" class="card-footer bg-white d-flex justify-content-between">
                <Link v-if="prescriptions.prev_page_url" :href="prescriptions.prev_page_url" class="btn btn-sm btn-outline-secondary">Previous prescriptions</Link>
                <span v-else />
                <Link v-if="prescriptions.next_page_url" :href="prescriptions.next_page_url" class="btn btn-sm btn-outline-secondary">More prescriptions</Link>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.pharmacy-analytics-card {
    background: linear-gradient(145deg, #fff 0%, #fbfdff 100%);
    border: 1px solid #e6edf3;
    border-radius: 0.9rem;
    box-shadow: 0 5px 20px rgb(34 56 84 / 4%);
    min-height: 172px;
    overflow: hidden;
    padding: 1.15rem 1.2rem 1rem;
    position: relative;
}

.pharmacy-analytics-top {
    align-items: center;
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.pharmacy-analytics-icon {
    align-items: center;
    border-radius: 0.72rem;
    display: inline-flex;
    font-size: 1.15rem;
    height: 42px;
    justify-content: center;
    width: 42px;
}

.pharmacy-analytics-context {
    background: #f3f7fb;
    border-radius: 2rem;
    color: #78879a;
    font-size: 0.65rem;
    font-weight: 600;
    padding: 0.35rem 0.6rem;
}

.pharmacy-analytics-label {
    color: #78879a;
    font-size: 0.78rem;
    font-weight: 600;
}

.pharmacy-analytics-value {
    color: #203047;
    font-size: 1.9rem;
    font-weight: 700;
    letter-spacing: -0.045em;
    line-height: 1.2;
    margin-top: 0.15rem;
}

.pharmacy-analytics-detail {
    color: #8795a7;
    font-size: 0.7rem;
    margin-top: 0.45rem;
}

.pharmacy-analytics-accent {
    background: currentColor;
    border-radius: 50%;
    bottom: -34px;
    height: 70px;
    opacity: 0.06;
    position: absolute;
    right: -24px;
    width: 70px;
}

.pharmacy-card-blue { color: #2878d0; }
.pharmacy-card-blue .pharmacy-analytics-icon { background: #eaf3ff; }
.pharmacy-card-teal { color: #168b89; }
.pharmacy-card-teal .pharmacy-analytics-icon { background: #e6f6f5; }
.pharmacy-card-amber { color: #cc851d; }
.pharmacy-card-amber .pharmacy-analytics-icon { background: #fff4e3; }
.pharmacy-card-green { color: #16956a; }
.pharmacy-card-green .pharmacy-analytics-icon { background: #e8f7f1; }
.pharmacy-card-violet { color: #7855c4; }
.pharmacy-card-violet .pharmacy-analytics-icon { background: #f1edff; }

.stock-quantity {
    color: #203047;
    font-weight: 700;
}

.stock-quantity span,
.stock-caption {
    color: #8795a7;
    font-size: 0.68rem;
    font-weight: 400;
}

.stock-meter,
.dispense-stock-track {
    background: #eaf0f4;
    border-radius: 1rem;
    height: 5px;
    margin-top: 0.4rem;
    overflow: hidden;
}

.stock-meter span,
.dispense-stock-track span {
    border-radius: inherit;
    display: block;
    height: 100%;
    transition: width 0.2s ease;
}

.stock-meter-low { background: #e8a43a; }
.stock-meter-ok { background: #20a577; }

.stock-caption {
    margin-top: 0.2rem;
}

.dispense-stock-monitor {
    background: #f0f8f6;
    border: 1px solid #d8eee7;
    border-radius: 0.65rem;
    color: #536b68;
    font-size: 0.76rem;
    padding: 0.7rem 0.8rem;
}

.dispense-stock-monitor strong {
    color: #203047;
    margin-left: 0.2rem;
}

.dispense-stock-monitor .dispense-stock-track {
    height: 6px;
    margin: 0.55rem 0 0.25rem;
}

.dispense-stock-track span {
    background: #20a577;
}

.dispense-stock-empty {
    background: #fff6f3;
    border-color: #f4d8d1;
}

.dispense-stock-empty .dispense-stock-track span {
    background: #dc6555;
}
</style>
