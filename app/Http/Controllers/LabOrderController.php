<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\LabOrder;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LabOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $hospital = Hospital::default();
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        if (! in_array($status, LabOrder::STATUSES, true)) {
            $status = '';
        }

        $orders = LabOrder::query()
            ->where('hospital_id', $hospital->id)
            ->with([
                'patient:id,mrn,first_name,last_name,phone',
                'orderedBy:id,name',
                'processedBy:id,name',
            ])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(fn ($sub) => $sub
                    ->where('test_name', 'like', $like)
                    ->orWhere('category', 'like', $like)
                    ->orWhereHas('patient', fn ($patient) => $patient
                        ->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('mrn', 'like', $like)));
            })
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'in_progress' THEN 1 WHEN status = 'completed' THEN 2 ELSE 3 END")
            ->orderByDesc('ordered_at')
            ->paginate(12)
            ->withQueryString();

        $counts = LabOrder::query()
            ->where('hospital_id', $hospital->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            ")
            ->first();

        return Inertia::render('Lab/Index', [
            'orders' => $orders,
            'patients' => Patient::query()
                ->where('hospital_id', $hospital->id)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(100)
                ->get(['id', 'mrn', 'first_name', 'last_name']),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'summary' => [
                'total' => (int) ($counts->total ?? 0),
                'pending' => (int) ($counts->pending ?? 0),
                'inProgress' => (int) ($counts->in_progress ?? 0),
                'completed' => (int) ($counts->completed ?? 0),
            ],
            'canManage' => $request->user()->can('lab.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $hospital = Hospital::default();
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')->where('hospital_id', $hospital->id)],
            'test_name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['required', 'in:'.implode(',', LabOrder::PRIORITIES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        LabOrder::create([
            'hospital_id' => $hospital->id,
            'patient_id' => $validated['patient_id'],
            'ordered_by' => $request->user()->id,
            'test_name' => $validated['test_name'],
            'category' => $validated['category'] ?? null,
            'priority' => $validated['priority'],
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
            'ordered_at' => now(),
        ]);

        return back()->with('success', 'Laboratory test ordered.');
    }

    public function update(Request $request, LabOrder $labOrder): RedirectResponse
    {
        abort_unless($labOrder->hospital_id === Hospital::default()->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', LabOrder::STATUSES)],
            'result_summary' => ['nullable', 'string', 'max:2000'],
            'result_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $status = $validated['status'];
        $labOrder->update([
            'status' => $status,
            'result_summary' => $validated['result_summary'] ?? $labOrder->result_summary,
            'result_notes' => $validated['result_notes'] ?? $labOrder->result_notes,
            'processed_by' => $request->user()->id,
            'completed_at' => $status === 'completed' ? ($labOrder->completed_at ?? now()) : null,
        ]);

        return back()->with('success', 'Laboratory order updated.');
    }
}
