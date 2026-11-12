<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\PharmacyDispensation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PharmacyController extends Controller
{
    public function index(Request $request): Response
    {
        $hospital = Hospital::default();
        $search = trim((string) $request->query('search', ''));

        $medicines = Medicine::query()
            ->where('hospital_id', $hospital->id)
            ->where('is_active', true)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where('name', 'like', $like)
                    ->orWhere('generic_name', 'like', $like)
                    ->orWhere('strength', 'like', $like);
            }))
            ->orderBy('name')
            ->paginate(10, ['*'], 'medicines_page')
            ->withQueryString();

        $prescriptions = MedicalRecord::query()
            ->whereNotNull('prescription')
            ->where('prescription', '!=', '')
            ->whereHas('patient', fn ($query) => $query->where('hospital_id', $hospital->id))
            ->with([
                'patient:id,mrn,first_name,last_name',
                'doctor:id,first_name,last_name',
                'dispensations' => fn ($query) => $query->orderByDesc('dispensed_at'),
                'dispensations.items.medicine:id,name,strength,dosage_form',
                'dispensations.dispensedBy:id,name',
            ])
            ->when($search !== '', fn ($query) => $query->whereHas('patient', fn ($patient) => $patient
                ->where('first_name', 'like', '%'.$search.'%')
                ->orWhere('last_name', 'like', '%'.$search.'%')
                ->orWhere('mrn', 'like', '%'.$search.'%')))
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'prescriptions_page')
            ->withQueryString();

        return Inertia::render('Pharmacy/Index', [
            'medicines' => $medicines,
            'prescriptions' => $prescriptions,
            'currency' => $hospital->currency ?: 'USD',
            'filters' => ['search' => $search],
            'summary' => [
                'medicineCount' => Medicine::where('hospital_id', $hospital->id)->where('is_active', true)->count(),
                'unitsInStock' => (int) Medicine::where('hospital_id', $hospital->id)
                    ->where('is_active', true)
                    ->sum('stock_quantity'),
                'lowStockCount' => Medicine::where('hospital_id', $hospital->id)
                    ->where('is_active', true)
                    ->whereColumn('stock_quantity', '<=', 'reorder_level')
                    ->count(),
                'prescriptionCount' => MedicalRecord::query()
                    ->whereNotNull('prescription')
                    ->where('prescription', '!=', '')
                    ->whereHas('patient', fn ($query) => $query->where('hospital_id', $hospital->id))
                    ->count(),
            ],
            'canManage' => $request->user()->can('pharmacy.manage'),
        ]);
    }

    public function storeMedicine(Request $request): RedirectResponse
    {
        $hospital = Hospital::default();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'strength' => ['nullable', 'string', 'max:100'],
            'dosage_form' => ['nullable', 'string', 'max:100'],
            'unit_price' => ['required', 'numeric', 'gt:0', 'max:100000000', 'decimal:0,2'],
            'initial_stock' => ['required', 'integer', 'min:0', 'max:10000000'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:10000000'],
        ]);

        DB::transaction(function () use ($validated, $hospital, $request) {
            $medicine = Medicine::create([
                'hospital_id' => $hospital->id,
                'name' => $validated['name'],
                'generic_name' => $validated['generic_name'] ?? null,
                'strength' => $validated['strength'] ?? null,
                'dosage_form' => $validated['dosage_form'] ?? null,
                'unit_price' => $validated['unit_price'],
                'stock_quantity' => $validated['initial_stock'],
                'reorder_level' => $validated['reorder_level'],
                'is_active' => true,
            ]);

            if ($validated['initial_stock'] > 0) {
                $medicine->stockMovements()->create([
                    'recorded_by' => $request->user()->id,
                    'type' => 'received',
                    'quantity' => $validated['initial_stock'],
                    'reference' => 'Opening stock',
                    'recorded_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Medicine added to inventory.');
    }

    public function updateMedicinePrice(Request $request, int $medicine): RedirectResponse
    {
        $validated = $request->validate([
            'unit_price' => ['required', 'numeric', 'gt:0', 'max:100000000', 'decimal:0,2'],
        ]);
        $hospital = Hospital::default();

        Medicine::query()
            ->where('hospital_id', $hospital->id)
            ->where('is_active', true)
            ->findOrFail($medicine)
            ->update(['unit_price' => $validated['unit_price']]);

        return back()->with('success', 'Medicine price updated.');
    }

    public function updateMedicine(Request $request, int $medicine): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'strength' => ['nullable', 'string', 'max:100'],
            'dosage_form' => ['nullable', 'string', 'max:100'],
            'unit_price' => ['required', 'numeric', 'gt:0', 'max:100000000', 'decimal:0,2'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:10000000'],
        ]);
        $hospital = Hospital::default();

        Medicine::query()
            ->where('hospital_id', $hospital->id)
            ->where('is_active', true)
            ->findOrFail($medicine)
            ->update($validated);

        return back()->with('success', 'Medicine details updated.');
    }

    public function receiveStock(Request $request, int $medicine): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10000000'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);
        $hospital = Hospital::default();

        DB::transaction(function () use ($medicine, $hospital, $validated, $request) {
            $medicine = Medicine::query()
                ->where('hospital_id', $hospital->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->findOrFail($medicine);

            $medicine->increment('stock_quantity', $validated['quantity']);
            $medicine->stockMovements()->create([
                'recorded_by' => $request->user()->id,
                'type' => 'received',
                'quantity' => $validated['quantity'],
                'reference' => $validated['reference'] ?? null,
                'recorded_at' => now(),
            ]);
        });

        return back()->with('success', 'Stock received successfully.');
    }

    public function dispense(Request $request, int $record): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.medicine_id' => ['required', 'integer', 'distinct', 'exists:medicines,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000000'],
        ]);
        $hospital = Hospital::default();

        $invoice = DB::transaction(function () use ($record, $hospital, $validated, $request) {
            $medicalRecord = MedicalRecord::query()
                ->whereNotNull('prescription')
                ->where('prescription', '!=', '')
                ->whereHas('patient', fn ($query) => $query->where('hospital_id', $hospital->id))
                ->with('patient')
                ->lockForUpdate()
                ->findOrFail($record);

            $pricedItems = [];
            $subtotalCents = 0;

            foreach ($validated['items'] as $item) {
                $medicine = Medicine::query()
                    ->where('hospital_id', $hospital->id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->findOrFail($item['medicine_id']);

                if ($medicine->stock_quantity < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$medicine->name}.",
                    ]);
                }

                $unitPriceCents = (int) round((float) $medicine->unit_price * 100);
                if ($unitPriceCents <= 0) {
                    throw ValidationException::withMessages([
                        'items' => "Set a price greater than zero for {$medicine->name} before dispensing.",
                    ]);
                }

                $amountCents = $unitPriceCents * (int) $item['quantity'];
                $subtotalCents += $amountCents;

                if ($subtotalCents > 999_999_999_999) {
                    throw ValidationException::withMessages([
                        'items' => 'The dispensed medicine total exceeds the supported invoice amount.',
                    ]);
                }

                $pricedItems[] = [
                    'medicine' => $medicine,
                    'quantity' => (int) $item['quantity'],
                    'unit_price_cents' => $unitPriceCents,
                    'amount_cents' => $amountCents,
                ];
            }

            $dispensation = PharmacyDispensation::create([
                'medical_record_id' => $medicalRecord->id,
                'dispensed_by' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
                'dispensed_at' => now(),
            ]);

            $invoice = Invoice::create([
                'hospital_id' => $hospital->id,
                'patient_id' => $medicalRecord->patient_id,
                'invoice_number' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'status' => 'unpaid',
                'subtotal' => number_format($subtotalCents / 100, 2, '.', ''),
                'discount' => '0.00',
                'total' => number_format($subtotalCents / 100, 2, '.', ''),
                'amount_paid' => '0.00',
                'notes' => 'Automatically generated for pharmacy dispensing of medical record #'.$medicalRecord->id.'.',
                'issued_at' => today(),
            ]);

            foreach ($pricedItems as $item) {
                $medicine = $item['medicine'];
                $unitPrice = number_format($item['unit_price_cents'] / 100, 2, '.', '');
                $amount = number_format($item['amount_cents'] / 100, 2, '.', '');
                $description = implode(' · ', array_filter([
                    $medicine->name,
                    $medicine->strength,
                    $medicine->dosage_form,
                ]));
                $dispensation->items()->create([
                    'medicine_id' => $medicine->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                ]);
                $invoice->items()->create([
                    'description' => $description,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                ]);
                $medicine->decrement('stock_quantity', $item['quantity']);
                $medicine->stockMovements()->create([
                    'recorded_by' => $request->user()->id,
                    'dispensation_id' => $dispensation->id,
                    'type' => 'dispensed',
                    'quantity' => $item['quantity'],
                    'reference' => 'Medical record #'.$medicalRecord->id,
                    'recorded_at' => now(),
                ]);
            }

            $dispensation->update(['invoice_id' => $invoice->id]);

            return $invoice;
        });

        return back()->with('success', "Dispensing recorded, stock updated, and invoice {$invoice->invoice_number} generated.");
    }
}
