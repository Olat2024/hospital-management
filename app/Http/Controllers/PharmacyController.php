<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\PharmacyDispensation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        DB::transaction(function () use ($record, $hospital, $validated, $request) {
            $medicalRecord = MedicalRecord::query()
                ->whereNotNull('prescription')
                ->where('prescription', '!=', '')
                ->whereHas('patient', fn ($query) => $query->where('hospital_id', $hospital->id))
                ->lockForUpdate()
                ->findOrFail($record);

            $dispensation = PharmacyDispensation::create([
                'medical_record_id' => $medicalRecord->id,
                'dispensed_by' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
                'dispensed_at' => now(),
            ]);

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

                $dispensation->items()->create([
                    'medicine_id' => $medicine->id,
                    'quantity' => $item['quantity'],
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
        });

        return back()->with('success', 'Dispensing recorded and stock updated.');
    }
}
