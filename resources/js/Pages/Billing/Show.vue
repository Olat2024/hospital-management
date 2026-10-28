<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmAction } from '@/Composables/useSweetAlert';

const props = defineProps({
    invoice: Object,
    currency: String,
    paymentMethods: Array,
    can: Object,
});

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: props.currency || 'USD',
});
const money = (amount) => currencyFormat.format(Number(amount || 0));
const paymentForm = useForm({
    amount: Number(props.invoice.balance).toFixed(2),
    method: 'cash',
    reference: '',
});
const statusClass = {
    unpaid: 'bg-warning text-dark',
    partial: 'bg-info text-dark',
    paid: 'bg-success',
    void: 'bg-secondary',
};

const recordPayment = () => paymentForm.post(route('billing.payments.store', props.invoice.id), {
    preserveScroll: true,
});
const voidInvoice = async () => {
    const confirmed = await confirmAction({
        title: 'Void this invoice?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Void invoice',
    });

    if (confirmed) {
        router.post(route('billing.void', props.invoice.id), {}, { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout>
        <div class="clinical-page invoice-detail">
        <div class="clinical-page-heading">
            <div>
                <div class="clinical-eyebrow">Invoice details</div>
                <div class="d-flex align-items-center gap-2">
                    <h1 class="clinical-page-title mb-0">{{ invoice.invoice_number }}</h1>
                    <span class="badge text-capitalize" :class="statusClass[invoice.status]">{{ invoice.status }}</span>
                </div>
                <p class="text-muted mb-0">Issued {{ invoice.issued_at }}<span v-if="invoice.due_at"> · Due {{ invoice.due_at }}</span></p>
            </div>
            <div class="d-flex gap-2">
                <button v-if="can.void" type="button" class="btn btn-outline-danger" @click="voidInvoice">
                    <i class="bi bi-x-circle me-1"></i> Void invoice
                </button>
                <Link :href="route('billing.index')" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> All invoices
                </Link>
            </div>
        </div>

        <div v-if="paymentForm.errors.invoice" class="alert alert-danger">{{ paymentForm.errors.invoice }}</div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card clinical-panel mb-4">
                    <div class="card-header">
                        <h2>Patient</h2>
                    </div>
                    <div class="card-body">
                        <div class="patient-invoice-summary">
                            <span class="patient-invoice-icon"><i class="bi bi-person"></i></span>
                            <div>
                                <div class="fw-semibold">{{ invoice.patient.first_name }} {{ invoice.patient.last_name }}</div>
                                <div class="text-muted small">MRN: {{ invoice.patient.mrn }}</div>
                                <div v-if="invoice.patient.phone" class="text-muted small">{{ invoice.patient.phone }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card clinical-panel mb-4">
                    <div class="card-header"><h2>Invoice items</h2></div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Amount</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in invoice.items" :key="item.id">
                                    <td>{{ item.description }}</td>
                                    <td class="text-end">{{ item.quantity }}</td>
                                    <td class="text-end">{{ money(item.unit_price) }}</td>
                                    <td class="text-end">{{ money(item.amount) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr><th colspan="3" class="text-end">Subtotal</th><td class="text-end">{{ money(invoice.subtotal) }}</td></tr>
                                <tr><th colspan="3" class="text-end">Discount</th><td class="text-end">−{{ money(invoice.discount) }}</td></tr>
                                <tr class="invoice-total-row"><th colspan="3" class="text-end">Total</th><th class="text-end">{{ money(invoice.total) }}</th></tr>
                                <tr><th colspan="3" class="text-end">Paid</th><td class="text-end text-success">{{ money(invoice.amount_paid) }}</td></tr>
                                <tr class="invoice-balance-row"><th colspan="3" class="text-end">Balance due</th><th class="text-end">{{ money(invoice.balance) }}</th></tr>
                            </tfoot>
                        </table>
                    </div>
                    <div v-if="invoice.notes" class="card-body border-top">
                        <div class="small text-muted text-uppercase mb-1">Notes</div>
                        <p class="mb-0">{{ invoice.notes }}</p>
                    </div>
                </div>

                <div class="card clinical-panel">
                    <div class="card-header"><h2>Payment history</h2></div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr><th>Date</th><th>Method</th><th>Reference</th><th>Recorded by</th><th class="text-end">Amount</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="payment in invoice.payments" :key="payment.id">
                                    <td>{{ new Date(payment.paid_at).toLocaleString() }}</td>
                                    <td class="text-capitalize">{{ payment.method }}</td>
                                    <td>{{ payment.reference || '—' }}</td>
                                    <td>{{ payment.recorder?.name || 'System' }}</td>
                                    <td class="text-end">{{ money(payment.amount) }}</td>
                                </tr>
                                <tr v-if="invoice.payments.length === 0">
                                    <td colspan="5" class="text-center py-4 text-muted">No payments recorded.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4" v-if="can.manage && invoice.balance > 0 && invoice.status !== 'void'">
                <div class="card clinical-panel payment-entry-card">
                    <div class="card-header"><h2>Record a payment</h2></div>
                    <div class="card-body">
                        <form @submit.prevent="recordPayment">
                            <div class="mb-3">
                                <label for="payment-amount" class="form-label">Amount ({{ currency }})</label>
                                <input id="payment-amount" v-model="paymentForm.amount" type="number" min="0.01" :max="invoice.balance" step="0.01" class="form-control" :class="{ 'is-invalid': paymentForm.errors.amount }">
                                <div class="invalid-feedback">{{ paymentForm.errors.amount }}</div>
                                <div class="form-text">Outstanding balance: {{ money(invoice.balance) }}</div>
                            </div>
                            <div class="mb-3">
                                <label for="payment-method" class="form-label">Payment method</label>
                                <select id="payment-method" v-model="paymentForm.method" class="form-select" :class="{ 'is-invalid': paymentForm.errors.method }">
                                    <option v-for="method in paymentMethods" :key="method" :value="method" class="text-capitalize">{{ method }}</option>
                                </select>
                                <div class="invalid-feedback">{{ paymentForm.errors.method }}</div>
                            </div>
                            <div class="mb-3">
                                <label for="payment-reference" class="form-label">Reference <span class="text-muted">(optional)</span></label>
                                <input id="payment-reference" v-model="paymentForm.reference" class="form-control" :class="{ 'is-invalid': paymentForm.errors.reference }">
                                <div class="invalid-feedback">{{ paymentForm.errors.reference }}</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" :disabled="paymentForm.processing">
                                <i class="bi bi-check2-circle me-1"></i> Record payment
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.patient-invoice-summary {
    align-items: center;
    display: flex;
    gap: 0.9rem;
}

.patient-invoice-icon {
    align-items: center;
    background: linear-gradient(145deg, #e8f4fa, #e5f7f2);
    border-radius: 0.75rem;
    color: #176b87;
    display: inline-flex;
    flex: 0 0 auto;
    font-size: 1.2rem;
    height: 44px;
    justify-content: center;
    width: 44px;
}

.invoice-total-row th,
.invoice-total-row td {
    border-top: 1px solid #e8edf3;
    color: #203047;
    font-size: 1.02rem;
    padding-top: 0.85rem !important;
}

.invoice-balance-row th,
.invoice-balance-row td {
    background: #edf6fa;
    color: #176b87;
    font-size: 0.95rem;
}

.payment-entry-card {
    position: sticky;
    top: 1rem;
}

@media (max-width: 991.98px) {
    .payment-entry-card {
        position: static;
    }
}
</style>
