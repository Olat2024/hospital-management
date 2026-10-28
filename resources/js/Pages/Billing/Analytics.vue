<script setup>
import { computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    filters: Object,
    paymentMethods: Array,
    currency: String,
    summary: Object,
    methods: Array,
    daily: Array,
    invoiceStatuses: Array,
    payments: Object,
});

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: props.currency || 'USD',
    maximumFractionDigits: 2,
});
const money = (amount) => currencyFormat.format(Number(amount || 0));
const methodColors = ['#2878d0', '#16a779', '#f3a83b', '#8d68ce'];
const statusColors = ['#e8a43a', '#3989dc', '#20a577'];

const chart = computed(() => {
    const plot = { left: 48, top: 18, width: 690, height: 194 };
    const baseline = plot.top + plot.height;
    const maxTotal = Math.max(1, ...props.daily.map((day) => Number(day.total)));
    const maxCount = Math.max(1, ...props.daily.map((day) => Number(day.paymentCount)));
    const slot = props.daily.length ? plot.width / props.daily.length : plot.width;
    const bars = props.daily.map((day, index) => {
        const height = Number(day.total) / maxTotal * (plot.height - 8);

        return {
            ...day,
            x: plot.left + index * slot + slot * 0.2,
            y: baseline - height,
            width: Math.max(2, slot * 0.6),
            height,
            center: plot.left + index * slot + slot / 2,
            lineY: baseline - Number(day.paymentCount) / maxCount * (plot.height - 18),
            label: new Date(`${day.date}T00:00:00`).toLocaleDateString(undefined, {
                month: 'short',
                day: 'numeric',
            }),
        };
    });
    const labelIndexes = props.daily.length <= 5
        ? props.daily.map((_, index) => index)
        : [...new Set([0, 0.25, 0.5, 0.75, 1].map((point) => Math.round((props.daily.length - 1) * point)))];

    return {
        plot,
        baseline,
        bars,
        maxTotal,
        line: bars.map((bar) => `${bar.center},${bar.lineY}`).join(' '),
        labels: labelIndexes.map((index) => bars[index]),
    };
});

const methodSegments = computed(() => {
    const total = props.methods.reduce((sum, method) => sum + Number(method.total), 0);
    const circumference = 2 * Math.PI * 66;
    let offset = 0;

    return props.methods.map((method, index) => {
        const fraction = total ? Number(method.total) / total : 0;
        const segment = {
            ...method,
            color: methodColors[index % methodColors.length],
            share: fraction * 100,
            dash: `${fraction * circumference} ${circumference}`,
            offset: -offset,
        };
        offset += fraction * circumference;

        return segment;
    });
});

const statusSegments = computed(() => {
    const total = props.invoiceStatuses.reduce((sum, status) => sum + Number(status.invoiceCount), 0);
    const circumference = 2 * Math.PI * 66;
    let offset = 0;

    return props.invoiceStatuses.map((status, index) => {
        const fraction = total ? Number(status.invoiceCount) / total : 0;
        const segment = {
            ...status,
            color: statusColors[index % statusColors.length],
            share: fraction * 100,
            dash: `${fraction * circumference} ${circumference}`,
            offset: -offset,
        };
        offset += fraction * circumference;

        return segment;
    });
});

const collectionRate = computed(() => Math.min(100, Math.max(0, Number(props.summary.collectionRate || 0))));

const applyFilters = (event) => {
    router.get(route('billing.analytics'), {
        from: event.target.from.value,
        to: event.target.to.value,
        method: event.target.method.value || undefined,
    }, { preserveState: true, replace: true });
};

const titleCase = (value) => value.charAt(0).toUpperCase() + value.slice(1);
</script>

<template>
    <AppLayout>
        <div class="analytics-page">
            <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <div class="analytics-eyebrow">Revenue overview</div>
                    <h1 class="analytics-title mb-1">Payment analytics</h1>
                    <p class="text-muted mb-0">Monitor collections, payment channels, and invoice performance.</p>
                </div>
                <Link :href="route('billing.index')" class="btn btn-outline-primary analytics-back">
                    <i class="bi bi-arrow-left me-1"></i> Back to invoices
                </Link>
            </header>

            <section class="analytics-filter card border-0 mb-4">
                <form class="row g-3 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-sm-6 col-lg-3">
                        <label for="payments-from" class="form-label">Date range</label>
                        <input id="payments-from" name="from" type="date" class="form-control" :value="filters.from" required>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label for="payments-to" class="form-label">Through</label>
                        <input id="payments-to" name="to" type="date" class="form-control" :value="filters.to" :min="filters.from" required>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label for="payments-method" class="form-label">Payment method</label>
                        <select id="payments-method" name="method" class="form-select" :value="filters.method">
                            <option value="">All methods</option>
                            <option v-for="method in paymentMethods" :key="method" :value="method">
                                {{ titleCase(method) }}
                            </option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-3 d-grid d-sm-flex justify-content-sm-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-calendar-check me-1"></i> Update report
                        </button>
                    </div>
                </form>
            </section>

            <section class="row g-3 mb-4" aria-label="Payment summary">
                <div class="col-sm-6 col-xl-3">
                    <article class="analytics-kpi card h-100">
                        <div class="card-body">
                            <div class="kpi-top"><span class="kpi-icon kpi-blue"><i class="bi bi-cash-stack"></i></span><span class="kpi-caption">Selected period</span></div>
                            <div class="kpi-label">Total collected</div>
                            <div class="kpi-value">{{ money(summary.collected) }}</div>
                            <div class="kpi-foot"><i class="bi bi-graph-up-arrow me-1"></i> {{ filters.from }} – {{ filters.to }}</div>
                        </div>
                    </article>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <article class="analytics-kpi card h-100">
                        <div class="card-body">
                            <div class="kpi-top"><span class="kpi-icon kpi-green"><i class="bi bi-receipt"></i></span><span class="kpi-caption">Selected period</span></div>
                            <div class="kpi-label">Payments received</div>
                            <div class="kpi-value">{{ summary.paymentCount }}</div>
                            <div class="kpi-foot">Average {{ money(summary.averagePayment) }} per payment</div>
                        </div>
                    </article>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <article class="analytics-kpi card h-100">
                        <div class="card-body">
                            <div class="kpi-top"><span class="kpi-icon kpi-amber"><i class="bi bi-hourglass-split"></i></span><span class="kpi-caption">Current balance</span></div>
                            <div class="kpi-label">Outstanding invoices</div>
                            <div class="kpi-value">{{ money(summary.outstanding) }}</div>
                            <div class="kpi-foot">Across unpaid and partially paid invoices</div>
                        </div>
                    </article>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <article class="analytics-kpi card h-100">
                        <div class="card-body">
                            <div class="kpi-top"><span class="kpi-icon kpi-violet"><i class="bi bi-pie-chart"></i></span><span class="kpi-caption">Invoice portfolio</span></div>
                            <div class="kpi-label">Collection rate</div>
                            <div class="kpi-value">{{ summary.collectionRate }}<span class="kpi-percent">%</span></div>
                            <div class="kpi-foot">{{ money(summary.totalCollected) }} collected of {{ money(summary.totalInvoiced) }} invoiced</div>
                        </div>
                    </article>
                </div>
            </section>

            <section class="row g-4 mb-4">
                <div class="col-lg-8">
                    <article class="analytics-panel card h-100">
                        <div class="card-header">
                            <div>
                                <div class="panel-kicker">Collection activity</div>
                                <h2 class="panel-title">Daily payment trend</h2>
                            </div>
                            <span class="panel-period">{{ daily.length }} active day{{ daily.length === 1 ? '' : 's' }}</span>
                        </div>
                        <div class="card-body">
                            <div class="chart-legend">
                                <span><i class="legend-swatch legend-bar"></i> Collections</span>
                                <span><i class="legend-swatch legend-line"></i> Payment count</span>
                            </div>
                            <div v-if="chart.bars.length" class="trend-chart">
                                <svg viewBox="0 0 760 270" role="img" aria-label="Daily collections and payment count chart" preserveAspectRatio="xMidYMid meet">
                                    <defs>
                                        <linearGradient id="collection-bar-gradient" x1="0" x2="0" y1="0" y2="1">
                                            <stop offset="0%" stop-color="#3989dc" />
                                            <stop offset="100%" stop-color="#78b7f4" />
                                        </linearGradient>
                                    </defs>
                                    <g v-for="step in 5" :key="step">
                                        <line :x1="chart.plot.left" :x2="chart.plot.left + chart.plot.width" :y1="chart.plot.top + (step - 1) * chart.plot.height / 4" :y2="chart.plot.top + (step - 1) * chart.plot.height / 4" class="chart-gridline" />
                                        <text x="40" :y="chart.plot.top + (step - 1) * chart.plot.height / 4 + 4" class="chart-axis-label" text-anchor="end">
                                            {{ money(chart.maxTotal * (5 - step) / 4) }}
                                        </text>
                                    </g>
                                    <rect
                                        v-for="bar in chart.bars"
                                        :key="bar.date"
                                        :x="bar.x"
                                        :y="bar.y"
                                        :width="bar.width"
                                        :height="bar.height"
                                        rx="5"
                                        fill="url(#collection-bar-gradient)"
                                    >
                                        <title>{{ bar.label }}: {{ money(bar.total) }} across {{ bar.paymentCount }} payment{{ bar.paymentCount === 1 ? '' : 's' }}</title>
                                    </rect>
                                    <polyline v-if="chart.bars.length > 1" :points="chart.line" class="chart-count-line" />
                                    <circle v-for="bar in chart.bars" :key="`${bar.date}-point`" :cx="bar.center" :cy="bar.lineY" r="3.5" class="chart-count-dot">
                                        <title>{{ bar.label }}: {{ bar.paymentCount }} payment{{ bar.paymentCount === 1 ? '' : 's' }}</title>
                                    </circle>
                                    <line :x1="chart.plot.left" :x2="chart.plot.left + chart.plot.width" :y1="chart.baseline" :y2="chart.baseline" class="chart-axis-line" />
                                    <text v-for="bar in chart.labels" :key="`${bar.date}-label`" :x="bar.center" y="244" class="chart-axis-label" text-anchor="middle">{{ bar.label }}</text>
                                </svg>
                            </div>
                            <div v-else class="chart-empty">
                                <i class="bi bi-bar-chart-line"></i>
                                <div class="fw-semibold mt-2">No collection activity</div>
                                <div class="small">There are no payments recorded in this date range.</div>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="col-lg-4">
                    <article class="analytics-panel card h-100">
                        <div class="card-header">
                            <div>
                                <div class="panel-kicker">Where collections come from</div>
                                <h2 class="panel-title">Payment methods</h2>
                            </div>
                        </div>
                        <div class="card-body">
                            <div v-if="methodSegments.length" class="donut-wrap">
                                <svg viewBox="0 0 180 180" class="donut-chart" role="img" aria-label="Collections by payment method">
                                    <circle cx="90" cy="90" r="66" class="donut-track" />
                                    <circle
                                        v-for="segment in methodSegments"
                                        :key="segment.method"
                                        cx="90"
                                        cy="90"
                                        r="66"
                                        class="donut-segment"
                                        :stroke="segment.color"
                                        :stroke-dasharray="segment.dash"
                                        :stroke-dashoffset="segment.offset"
                                    />
                                    <text x="90" y="85" class="donut-center-label" text-anchor="middle">COLLECTED</text>
                                    <text x="90" y="106" class="donut-center-value" text-anchor="middle">{{ money(summary.collected) }}</text>
                                </svg>
                            </div>
                            <div v-else class="chart-empty chart-empty-compact">
                                <i class="bi bi-credit-card"></i>
                                <div class="fw-semibold mt-2">No methods to show</div>
                                <div class="small">Payments will appear here once recorded.</div>
                            </div>
                            <div v-if="methodSegments.length" class="donut-legend">
                                <div v-for="segment in methodSegments" :key="`${segment.method}-legend`" class="legend-row">
                                    <span class="legend-name"><i class="legend-swatch" :style="{ backgroundColor: segment.color }"></i>{{ titleCase(segment.method) }}</span>
                                    <span class="text-end"><strong>{{ money(segment.total) }}</strong><small>{{ segment.share.toFixed(1) }}% · {{ segment.paymentCount }} payment{{ segment.paymentCount === 1 ? '' : 's' }}</small></span>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section class="row g-4 mb-4">
                <div class="col-md-6 col-xl-4">
                    <article class="analytics-panel health-panel card h-100">
                        <div class="card-header">
                            <div>
                                <div class="panel-kicker">Invoice portfolio</div>
                                <h2 class="panel-title">Collection progress</h2>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <div class="rate-ring" :style="{ '--rate': `${collectionRate}%` }">
                                <div class="rate-ring-inner">
                                    <strong>{{ summary.collectionRate }}%</strong>
                                    <span>collected</span>
                                </div>
                            </div>
                            <div class="rate-total">{{ money(summary.totalInvoiced) }}</div>
                            <div class="small text-muted">Total invoiced across {{ summary.invoiceCount }} active invoice{{ summary.invoiceCount === 1 ? '' : 's' }}</div>
                            <div class="health-details">
                                <div><span><i class="status-dot dot-green"></i>Collected</span><strong>{{ money(summary.totalCollected) }}</strong></div>
                                <div><span><i class="status-dot dot-amber"></i>Outstanding</span><strong>{{ money(summary.outstanding) }}</strong></div>
                            </div>
                        </div>
                    </article>
                </div>
                <div class="col-md-6 col-xl-4">
                    <article class="analytics-panel card h-100">
                        <div class="card-header">
                            <div>
                                <div class="panel-kicker">Accounts receivable</div>
                                <h2 class="panel-title">Invoice status</h2>
                            </div>
                        </div>
                        <div class="card-body">
                            <div v-if="summary.invoiceCount" class="donut-wrap status-donut-wrap">
                                <svg viewBox="0 0 180 180" class="donut-chart" role="img" aria-label="Invoices by status">
                                    <circle cx="90" cy="90" r="66" class="donut-track" />
                                    <circle
                                        v-for="segment in statusSegments"
                                        :key="segment.status"
                                        cx="90"
                                        cy="90"
                                        r="66"
                                        class="donut-segment"
                                        :stroke="segment.color"
                                        :stroke-dasharray="segment.dash"
                                        :stroke-dashoffset="segment.offset"
                                    />
                                    <text x="90" y="85" class="donut-center-label" text-anchor="middle">INVOICES</text>
                                    <text x="90" y="108" class="donut-center-value" text-anchor="middle">{{ summary.invoiceCount }}</text>
                                </svg>
                            </div>
                            <div v-else class="chart-empty chart-empty-compact">
                                <i class="bi bi-file-earmark-text"></i>
                                <div class="fw-semibold mt-2">No invoices yet</div>
                                <div class="small">Invoice status totals will appear here.</div>
                            </div>
                            <div v-if="summary.invoiceCount" class="donut-legend status-legend">
                                <div v-for="segment in statusSegments" :key="`${segment.status}-legend`" class="legend-row">
                                    <span class="legend-name"><i class="legend-swatch" :style="{ backgroundColor: segment.color }"></i>{{ titleCase(segment.status) }}</span>
                                    <span class="text-end"><strong>{{ segment.invoiceCount }}</strong><small>{{ money(segment.outstanding) }} balance</small></span>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
                <div class="col-xl-4">
                    <article class="analytics-panel snapshot-panel card h-100">
                        <div class="card-header">
                            <div>
                                <div class="panel-kicker">At a glance</div>
                                <h2 class="panel-title">Period snapshot</h2>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="snapshot-highlight">
                                <span class="snapshot-icon"><i class="bi bi-calendar2-check"></i></span>
                                <div><small>Collected in selected period</small><strong>{{ money(summary.collected) }}</strong></div>
                            </div>
                            <div class="snapshot-row"><span>Payments received</span><strong>{{ summary.paymentCount }}</strong></div>
                            <div class="snapshot-row"><span>Average payment</span><strong>{{ money(summary.averagePayment) }}</strong></div>
                            <div class="snapshot-row"><span>Payment methods used</span><strong>{{ methods.length }}</strong></div>
                            <div class="snapshot-note mt-auto">
                                <i class="bi bi-info-circle me-1"></i>
                                Portfolio figures include all active invoices; activity figures use the selected dates.
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section class="analytics-panel card payments-panel">
                <div class="card-header">
                    <div>
                        <div class="panel-kicker">Detailed activity</div>
                        <h2 class="panel-title">Payments in selected period</h2>
                    </div>
                    <span class="payment-total">{{ payments.total }} record{{ payments.total === 1 ? '' : 's' }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table analytics-table align-middle mb-0">
                        <thead>
                            <tr><th>Date</th><th>Invoice</th><th>Patient</th><th>Method</th><th>Reference</th><th>Recorded by</th><th class="text-end">Amount</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="payment in payments.data" :key="payment.id">
                                <td class="text-nowrap">{{ new Date(payment.paid_at).toLocaleString() }}</td>
                                <td>
                                    <Link :href="route('billing.show', payment.invoice.id)" class="invoice-link">
                                        {{ payment.invoice.invoice_number }}
                                    </Link>
                                </td>
                                <td>{{ payment.invoice.patient.first_name }} {{ payment.invoice.patient.last_name }}</td>
                                <td><span class="method-pill">{{ titleCase(payment.method) }}</span></td>
                                <td>{{ payment.reference || '—' }}</td>
                                <td>{{ payment.recorder?.name || 'System' }}</td>
                                <td class="text-end fw-semibold">{{ money(payment.amount) }}</td>
                            </tr>
                            <tr v-if="payments.data.length === 0">
                                <td colspan="7" class="text-center py-5 text-muted">No payments in this date range.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="payments.last_page > 1" class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="small text-muted">Showing {{ payments.from }}–{{ payments.to }} of {{ payments.total }}</span>
                    <div class="d-flex gap-2">
                        <Link v-if="payments.prev_page_url" :href="payments.prev_page_url" class="btn btn-sm btn-outline-secondary">Previous</Link>
                        <Link v-if="payments.next_page_url" :href="payments.next_page_url" class="btn btn-sm btn-outline-secondary">Next</Link>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.analytics-page {
    --analytics-ink: #203047;
    --analytics-muted: #78879a;
    --analytics-border: #e8edf3;
    --analytics-blue: #2878d0;
    color: var(--analytics-ink);
    padding-bottom: 1.5rem;
}

.analytics-eyebrow,
.panel-kicker {
    color: var(--analytics-blue);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.11em;
    margin-bottom: 0.3rem;
    text-transform: uppercase;
}

.analytics-title {
    font-size: clamp(1.55rem, 3vw, 2rem);
    font-weight: 700;
    letter-spacing: -0.035em;
}

.analytics-back {
    border-radius: 0.65rem;
    font-weight: 600;
}

.analytics-filter,
.analytics-kpi,
.analytics-panel {
    border: 1px solid var(--analytics-border);
    border-radius: 0.9rem;
    box-shadow: 0 5px 20px rgb(34 56 84 / 4%);
}

.analytics-filter {
    padding: 1rem 1.25rem;
}

.analytics-filter .form-label {
    color: #536478;
    font-size: 0.8rem;
    font-weight: 600;
}

.analytics-filter .form-control {
    border-color: #dce4ed;
    border-radius: 0.6rem;
    min-height: 43px;
}

.analytics-kpi {
    background: linear-gradient(145deg, #fff 0%, #fbfdff 100%);
}

.analytics-kpi .card-body {
    padding: 1.2rem;
}

.kpi-top {
    align-items: center;
    display: flex;
    justify-content: space-between;
    margin-bottom: 1.1rem;
}

.kpi-icon,
.snapshot-icon {
    align-items: center;
    border-radius: 0.72rem;
    display: inline-flex;
    height: 42px;
    justify-content: center;
    width: 42px;
}

.kpi-icon {
    font-size: 1.15rem;
}

.kpi-blue { background: #eaf3ff; color: #2878d0; }
.kpi-green { background: #e8f7f1; color: #16956a; }
.kpi-amber { background: #fff4e3; color: #cc851d; }
.kpi-violet { background: #f1edff; color: #7855c4; }

.kpi-caption,
.kpi-label {
    color: var(--analytics-muted);
    font-size: 0.78rem;
}

.kpi-caption {
    text-align: right;
}

.kpi-label {
    font-weight: 600;
    margin-bottom: 0.3rem;
}

.kpi-value {
    font-size: clamp(1.3rem, 2vw, 1.75rem);
    font-weight: 700;
    letter-spacing: -0.04em;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.kpi-percent {
    font-size: 1rem;
    margin-left: 0.12rem;
}

.kpi-foot {
    color: var(--analytics-muted);
    font-size: 0.72rem;
    margin-top: 0.65rem;
}

.kpi-foot .bi {
    color: #17976d;
}

.analytics-panel {
    overflow: hidden;
}

.analytics-panel .card-header {
    align-items: center;
    background: #fff;
    border-bottom: 1px solid #eef1f5;
    display: flex;
    justify-content: space-between;
    min-height: 76px;
    padding: 1rem 1.25rem;
}

.analytics-panel .card-body {
    padding: 1.15rem 1.25rem 1.25rem;
}

.panel-kicker {
    color: #8a98a9;
    font-size: 0.64rem;
    margin-bottom: 0.2rem;
}

.panel-title {
    font-size: 1rem;
    font-weight: 700;
    margin: 0;
}

.panel-period,
.payment-total {
    background: #f3f7fb;
    border-radius: 2rem;
    color: #61758b;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.4rem 0.7rem;
    white-space: nowrap;
}

.chart-legend {
    color: #718198;
    display: flex;
    flex-wrap: wrap;
    font-size: 0.74rem;
    gap: 1.25rem;
    margin: 0 0 0.4rem 0.3rem;
}

.chart-legend span,
.legend-name {
    align-items: center;
    display: inline-flex;
    gap: 0.45rem;
}

.legend-swatch {
    border-radius: 3px;
    display: inline-block;
    flex: 0 0 auto;
    height: 9px;
    width: 9px;
}

.legend-bar { background: #3989dc; }
.legend-line { background: #f1a43b; border-radius: 50%; }

.trend-chart {
    overflow: hidden;
    width: 100%;
}

.trend-chart svg {
    display: block;
    height: auto;
    min-width: 520px;
    width: 100%;
}

.chart-gridline {
    stroke: #edf1f5;
    stroke-dasharray: 4 5;
    stroke-width: 1;
}

.chart-axis-line {
    stroke: #dfe6ee;
    stroke-width: 1;
}

.chart-axis-label {
    fill: #8795a7;
    font-size: 10px;
}

.chart-count-line {
    fill: none;
    stroke: #f1a43b;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-width: 2.5;
}

.chart-count-dot {
    fill: #fff;
    stroke: #f1a43b;
    stroke-width: 2;
}

.chart-empty {
    align-items: center;
    color: #8a98a9;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 260px;
    text-align: center;
}

.chart-empty > .bi {
    color: #b6c4d2;
    font-size: 2rem;
}

.chart-empty-compact {
    min-height: 165px;
}

.donut-wrap {
    display: flex;
    justify-content: center;
    margin: 0.15rem 0 0.8rem;
}

.donut-chart {
    height: 176px;
    max-width: 100%;
    overflow: visible;
    width: 176px;
}

.donut-track,
.donut-segment {
    fill: none;
    stroke-width: 17;
}

.donut-track {
    stroke: #edf1f5;
}

.donut-segment {
    stroke-linecap: butt;
    transform: rotate(-90deg);
    transform-origin: 90px 90px;
}

.donut-center-label {
    fill: #91a0b0;
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.donut-center-value {
    fill: #203047;
    font-size: 13px;
    font-weight: 700;
}

.donut-legend {
    border-top: 1px solid #eef1f5;
    padding-top: 0.5rem;
}

.legend-row {
    align-items: center;
    color: #52657a;
    display: flex;
    font-size: 0.78rem;
    justify-content: space-between;
    padding: 0.45rem 0;
}

.legend-row strong,
.legend-row small {
    display: block;
}

.legend-row strong {
    color: var(--analytics-ink);
    font-size: 0.78rem;
}

.legend-row small {
    color: var(--analytics-muted);
    font-size: 0.67rem;
    margin-top: 0.1rem;
}

.health-panel .card-body {
    min-height: 330px;
}

.rate-ring {
    align-items: center;
    background: conic-gradient(#20a577 var(--rate), #edf2f6 0);
    border-radius: 50%;
    display: flex;
    height: 144px;
    justify-content: center;
    position: relative;
    width: 144px;
}

.rate-ring-inner {
    align-items: center;
    background: #fff;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    height: 118px;
    justify-content: center;
    width: 118px;
}

.rate-ring-inner strong {
    color: #203047;
    font-size: 1.6rem;
    letter-spacing: -0.04em;
}

.rate-ring-inner span {
    color: var(--analytics-muted);
    font-size: 0.72rem;
}

.rate-total {
    font-size: 1.1rem;
    font-weight: 700;
    margin-top: 0.8rem;
}

.health-details {
    border-top: 1px solid #eef1f5;
    margin-top: 1.2rem;
    padding-top: 0.6rem;
    width: 100%;
}

.health-details > div {
    align-items: center;
    color: #68798d;
    display: flex;
    font-size: 0.76rem;
    justify-content: space-between;
    padding: 0.4rem 0;
}

.health-details strong {
    color: var(--analytics-ink);
}

.status-dot {
    border-radius: 50%;
    display: inline-block;
    height: 8px;
    margin-right: 0.45rem;
    width: 8px;
}

.dot-green { background: #20a577; }
.dot-amber { background: #e8a43a; }

.status-donut-wrap {
    margin-bottom: 0.7rem;
}

.snapshot-highlight {
    align-items: center;
    background: #f4f8fc;
    border-radius: 0.75rem;
    display: flex;
    gap: 0.75rem;
    margin-bottom: 0.7rem;
    padding: 0.9rem;
}

.snapshot-icon {
    background: #e2efff;
    color: #2878d0;
    flex: 0 0 auto;
    font-size: 1.1rem;
}

.snapshot-highlight small,
.snapshot-highlight strong {
    display: block;
}

.snapshot-highlight small {
    color: var(--analytics-muted);
    font-size: 0.69rem;
}

.snapshot-highlight strong {
    font-size: 1.12rem;
    margin-top: 0.15rem;
    overflow-wrap: anywhere;
}

.snapshot-row {
    align-items: center;
    border-bottom: 1px solid #eef1f5;
    color: #718198;
    display: flex;
    font-size: 0.78rem;
    justify-content: space-between;
    padding: 0.75rem 0.1rem;
}

.snapshot-row strong {
    color: var(--analytics-ink);
}

.snapshot-note {
    background: #fffaef;
    border-radius: 0.55rem;
    color: #8d7449;
    font-size: 0.69rem;
    line-height: 1.5;
    margin-top: 1.1rem;
    padding: 0.65rem;
}

.payments-panel .card-header {
    min-height: 72px;
}

.analytics-table {
    color: #536478;
    font-size: 0.8rem;
}

.analytics-table thead th {
    background: #f8fafc;
    border-bottom: 1px solid #e8edf3;
    color: #8492a3;
    font-size: 0.69rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    padding: 0.8rem 1rem;
    text-transform: uppercase;
    white-space: nowrap;
}

.analytics-table tbody td {
    border-color: #f0f3f6;
    padding: 0.85rem 1rem;
}

.invoice-link {
    color: #2878d0;
    font-weight: 600;
    text-decoration: none;
}

.invoice-link:hover {
    text-decoration: underline;
}

.method-pill {
    background: #edf5ff;
    border-radius: 2rem;
    color: #3974b2;
    display: inline-block;
    font-size: 0.68rem;
    font-weight: 600;
    padding: 0.3rem 0.55rem;
}

.payments-panel .card-footer {
    background: #fff;
    border-top: 1px solid #eef1f5;
    padding: 0.85rem 1.25rem;
}

@media (max-width: 575.98px) {
    .analytics-filter {
        padding: 0.9rem;
    }

    .analytics-panel .card-header {
        padding: 0.9rem 1rem;
    }

    .analytics-panel .card-body {
        padding: 1rem;
    }

    .panel-period {
        font-size: 0.64rem;
        padding: 0.35rem 0.5rem;
    }
}
</style>
