<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    invoices: Object,
    filters: Object,
    currency: String,
    can: Object,
});

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: props.currency || 'USD',
});

const money = (amount) => currencyFormat.format(Number(amount || 0));

const statusClass = {
    unpaid: 'bg-warning text-dark',
    partial: 'bg-info text-dark',
    paid: 'bg-success',
    void: 'bg-secondary',
};

const search = (event) => {
    router.get(route('billing.index'), {
        search: event.target.search.value,
        status: event.target.status.value,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <AppLayout>
        <div class="clinical-page">
        <div class="clinical-page-heading">
            <div>
                <div class="clinical-eyebrow">Revenue management</div>
                <h1 class="clinical-page-title">Billing &amp; invoices</h1>
                <p class="clinical-page-subtitle">Review invoices, track patient balances, and monitor collections.</p>
            </div>
            <div class="d-flex gap-2">
                <Link :href="route('billing.analytics')" class="btn btn-outline-primary py-2">
                    <i class="bi bi-graph-up me-1"></i> Payment analytics
                </Link>
                <Link v-if="can.create" :href="route('billing.create')" class="btn btn-primary py-2">
                    <i class="bi bi-plus-lg me-1"></i> Create invoice
                </Link>
            </div>
        </div>

        <div class="card clinical-panel">
            <div class="card-body border-bottom">
                <form class="row g-2" @submit.prevent="search">
                    <div class="col-md-6">
                        <label for="invoice-search" class="form-label">Find an invoice</label>
                        <input
                            id="invoice-search"
                            name="search"
                            class="form-control"
                            placeholder="Search invoice, patient, or MRN"
                            :value="filters.search"
                        >
                    </div>
                    <div class="col-md-3">
                        <label for="invoice-status" class="form-label">Payment status</label>
                        <select id="invoice-status" name="status" class="form-select" :value="filters.status">
                            <option value="">All statuses</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="partial">Partially paid</option>
                            <option value="paid">Paid</option>
                            <option value="void">Void</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i> Search
                        </button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Patient</th>
                            <th>Issued</th>
                            <th>Status</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Balance</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="invoice in invoices.data" :key="invoice.id">
                            <td class="fw-semibold">{{ invoice.invoice_number }}</td>
                            <td>
                                <template v-if="invoice.patient">
                                    {{ invoice.patient.first_name }} {{ invoice.patient.last_name }}
                                    <div class="small text-muted">{{ invoice.patient.mrn }}</div>
                                </template>
                                <span v-else class="text-muted">Patient unavailable</span>
                            </td>
                            <td>{{ invoice.issued_at }}</td>
                            <td><span class="badge text-capitalize" :class="statusClass[invoice.status]">{{ invoice.status }}</span></td>
                            <td class="text-end">{{ money(invoice.total) }}</td>
                            <td class="text-end">{{ money(invoice.balance) }}</td>
                            <td class="text-end">
                                <Link :href="route('billing.show', invoice.id)" class="btn btn-sm btn-outline-primary">
                                    Open
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="invoices.data.length === 0">
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-receipt d-block fs-3 mb-2"></i>
                                No invoices match your search.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="invoices.links.length > 3" class="card-footer bg-white">
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <template v-for="(link, index) in invoices.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="btn btn-sm"
                            :class="link.active ? 'btn-primary' : 'btn-outline-secondary'"
                            v-html="link.label"
                        />
                        <span v-else class="btn btn-sm btn-outline-secondary disabled" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
        </div>
    </AppLayout>
</template>
