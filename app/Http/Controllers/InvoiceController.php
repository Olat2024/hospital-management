<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $hospital = Hospital::default();
        $filters = [
            'status' => (string) $request->query('status', ''),
            'search' => trim((string) $request->query('search', '')),
        ];

        if (! in_array($filters['status'], Invoice::STATUSES, true)) {
            $filters['status'] = '';
        }

        $query = Invoice::query()
            ->where('hospital_id', $hospital->id)
            ->with('patient')
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['search'] !== '', function ($q) use ($filters) {
                $like = '%'.$filters['search'].'%';
                $q->where(function ($q) use ($like) {
                    $q->where('invoice_number', 'like', $like)
                        ->orWhereHas('patient', fn ($patient) => $patient
                            ->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('mrn', 'like', $like));
                });
            });

        return Inertia::render('Billing/Index', [
            'invoices' => $query->orderByDesc('issued_at')
                ->orderByDesc('id')
                ->paginate(12)
                ->withQueryString(),
            'filters' => $filters,
            'currency' => $hospital->currency ?: 'USD',
            'can' => [
                'create' => $request->user()->can('billing.create'),
            ],
        ]);
    }

    public function analytics(Request $request): Response
    {
        $hospital = Hospital::default();
        $defaultFrom = today()->subDays(29)->toDateString();
        $defaultTo = today()->toDateString();
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'method' => ['nullable', 'in:'.implode(',', Invoice::PAYMENT_METHODS)],
        ]);

        $fromDate = $validated['from'] ?? $defaultFrom;
        $toDate = $validated['to'] ?? $defaultTo;
        $method = $validated['method'] ?? '';

        if ($toDate < $fromDate) {
            throw ValidationException::withMessages([
                'to' => 'The end date must be on or after the start date.',
            ]);
        }

        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        $payments = Payment::query()
            ->whereHas('invoice', fn ($query) => $query->where('hospital_id', $hospital->id))
            ->whereBetween('paid_at', [$from, $to])
            ->when($method !== '', fn ($query) => $query->where('method', $method));

        $totals = (clone $payments)->selectRaw(
            'COUNT(*) as payment_count, COALESCE(SUM(amount), 0) as collected, COALESCE(AVG(amount), 0) as average_payment'
        )->first();

        $methods = (clone $payments)
            ->selectRaw('method, COUNT(*) as payment_count, COALESCE(SUM(amount), 0) as total')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        $daily = (clone $payments)
            ->selectRaw('DATE(paid_at) as date, COUNT(*) as payment_count, COALESCE(SUM(amount), 0) as total')
            ->groupByRaw('DATE(paid_at)')
            ->orderBy('date')
            ->get();

        $outstanding = Invoice::query()
            ->where('hospital_id', $hospital->id)
            ->whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('COALESCE(SUM(total - amount_paid), 0) as balance')
            ->value('balance');

        $invoicePortfolio = Invoice::query()
            ->where('hospital_id', $hospital->id)
            ->where('status', '!=', 'void')
            ->selectRaw(
                'COUNT(*) as invoice_count, COALESCE(SUM(total), 0) as total_invoiced, COALESCE(SUM(amount_paid), 0) as total_collected'
            )
            ->first();

        $invoiceStatuses = Invoice::query()
            ->where('hospital_id', $hospital->id)
            ->where('status', '!=', 'void')
            ->selectRaw('status, COUNT(*) as invoice_count, COALESCE(SUM(total - amount_paid), 0) as outstanding')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return Inertia::render('Billing/Analytics', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'method' => $method,
            ],
            'paymentMethods' => Invoice::PAYMENT_METHODS,
            'currency' => $hospital->currency ?: 'USD',
            'summary' => [
                'paymentCount' => (int) $totals->payment_count,
                'collected' => (float) $totals->collected,
                'averagePayment' => (float) $totals->average_payment,
                'outstanding' => (float) $outstanding,
                'invoiceCount' => (int) $invoicePortfolio->invoice_count,
                'totalInvoiced' => (float) $invoicePortfolio->total_invoiced,
                'totalCollected' => (float) $invoicePortfolio->total_collected,
                'collectionRate' => $invoicePortfolio->total_invoiced > 0
                    ? round(((float) $invoicePortfolio->total_collected / (float) $invoicePortfolio->total_invoiced) * 100, 1)
                    : 0,
            ],
            'invoiceStatuses' => collect(Invoice::STATUSES)
                ->reject(fn (string $status) => $status === 'void')
                ->map(fn (string $status) => [
                    'status' => $status,
                    'invoiceCount' => (int) ($invoiceStatuses[$status]->invoice_count ?? 0),
                    'outstanding' => (float) ($invoiceStatuses[$status]->outstanding ?? 0),
                ])
                ->values(),
            'methods' => $methods->map(fn ($method) => [
                'method' => $method->method,
                'paymentCount' => (int) $method->payment_count,
                'total' => (float) $method->total,
            ]),
            'daily' => $daily->map(fn ($day) => [
                'date' => $day->date,
                'paymentCount' => (int) $day->payment_count,
                'total' => (float) $day->total,
            ]),
            'payments' => (clone $payments)
                ->with(['invoice.patient', 'recorder'])
                ->orderByDesc('paid_at')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        $hospital = Hospital::default();

        return Inertia::render('Billing/Create', [
            'patients' => Patient::query()
                ->where('hospital_id', $hospital->id)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'mrn', 'first_name', 'last_name']),
            'currency' => $hospital->currency ?: 'USD',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $hospital = Hospital::default();
        $validated = $request->validate([
            'patient_id' => [
                'required',
                'integer',
                'exists:patients,id,hospital_id,'.$hospital->id,
            ],
            'issued_at' => ['required', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'discount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01', 'max:100000000', 'decimal:0,2'],
        ]);

        $subtotalCents = collect($validated['items'])
            ->sum(fn ($item) => self::toCents($item['unit_price']) * (int) $item['quantity']);
        $discountCents = self::toCents($validated['discount'] ?? '0');
        $totalCents = $subtotalCents - $discountCents;

        if ($subtotalCents > 999_999_999_999) {
            throw ValidationException::withMessages([
                'items' => 'The invoice subtotal exceeds the supported amount.',
            ]);
        }

        if ($discountCents > $subtotalCents) {
            throw ValidationException::withMessages([
                'discount' => 'The discount cannot exceed the invoice subtotal.',
            ]);
        }

        if ($totalCents <= 0) {
            throw ValidationException::withMessages([
                'discount' => 'The invoice total must be greater than zero.',
            ]);
        }

        $invoice = DB::transaction(function () use ($validated, $hospital, $subtotalCents, $discountCents, $totalCents) {
            $invoice = Invoice::create([
                'hospital_id' => $hospital->id,
                'patient_id' => $validated['patient_id'],
                'invoice_number' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'status' => 'unpaid',
                'subtotal' => self::fromCents($subtotalCents),
                'discount' => self::fromCents($discountCents),
                'total' => self::fromCents($totalCents),
                'amount_paid' => '0.00',
                'notes' => $validated['notes'] ?? null,
                'issued_at' => $validated['issued_at'],
                'due_at' => $validated['due_at'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $amountCents = self::toCents($item['unit_price']) * (int) $item['quantity'];
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => self::fromCents($amountCents),
                ]);
            }

            return $invoice;
        });

        return redirect()->route('billing.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    public function show(Request $request, int $invoice): Response
    {
        $hospital = Hospital::default();
        $invoice = Invoice::query()
            ->where('hospital_id', $hospital->id)
            ->with(['patient', 'items', 'payments.recorder'])
            ->findOrFail($invoice);

        return Inertia::render('Billing/Show', [
            'invoice' => $invoice,
            'currency' => $hospital->currency ?: 'USD',
            'paymentMethods' => Invoice::PAYMENT_METHODS,
            'can' => [
                'manage' => $request->user()->can('billing.manage'),
                'void' => $request->user()->can('billing.manage')
                    && $invoice->status === 'unpaid'
                    && $invoice->payments->isEmpty(),
            ],
        ]);
    }

    public function recordPayment(Request $request, int $invoice): RedirectResponse
    {
        $hospital = Hospital::default();
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'method' => ['required', 'in:'.implode(',', Invoice::PAYMENT_METHODS)],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($hospital, $invoice, $validated, $request) {
            $invoice = Invoice::query()
                ->where('hospital_id', $hospital->id)
                ->lockForUpdate()
                ->findOrFail($invoice);

            $amountCents = self::toCents($validated['amount']);
            $balanceCents = self::toCents($invoice->total) - self::toCents($invoice->amount_paid);

            if ($invoice->status === 'void') {
                throw ValidationException::withMessages([
                    'amount' => 'A payment cannot be recorded against a void invoice.',
                ]);
            }

            if ($amountCents > $balanceCents) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment cannot exceed the outstanding balance.',
                ]);
            }

            $invoice->payments()->create([
                'recorded_by' => $request->user()->id,
                'amount' => self::fromCents($amountCents),
                'method' => $validated['method'],
                'reference' => $validated['reference'] ?? null,
                'paid_at' => now(),
            ]);

            $invoice->amount_paid = self::fromCents(self::toCents($invoice->amount_paid) + $amountCents);
            $invoice->refreshStatus()->save();
        });

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function void(int $invoice): RedirectResponse
    {
        $hospital = Hospital::default();
        DB::transaction(function () use ($hospital, $invoice) {
            $invoice = Invoice::query()
                ->where('hospital_id', $hospital->id)
                ->lockForUpdate()
                ->withCount('payments')
                ->findOrFail($invoice);

            if ($invoice->status !== 'unpaid' || $invoice->payments_count > 0) {
                throw ValidationException::withMessages([
                    'invoice' => 'Only unpaid invoices with no recorded payments can be voided.',
                ]);
            }

            $invoice->update(['status' => 'void']);
        });

        return back()->with('success', 'Invoice voided successfully.');
    }

    private static function toCents(string|int|float $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private static function fromCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
