<?php

namespace App\Http\Controllers;

use App\Models\FamilyCard;
use App\Models\Hospital;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FamilyCardController extends Controller
{
    public function store(Request $request, int $patient): RedirectResponse
    {
        $validated = $request->validate([
            'family_card_number' => ['nullable', 'string', 'max:50'],
            'family_name' => ['required_without:family_card_number', 'nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:1000'],
            'relationship' => ['nullable', 'string', 'max:100'],
        ]);
        $hospital = Hospital::default();

        DB::transaction(function () use ($patient, $hospital, $validated) {
            $patient = Patient::query()
                ->where('hospital_id', $hospital->id)
                ->lockForUpdate()
                ->findOrFail($patient);

            if ($patient->family_card_id) {
                throw ValidationException::withMessages([
                    'family_card_number' => 'This patient already belongs to a family card.',
                ]);
            }

            if (! empty($validated['family_card_number'])) {
                $familyCard = FamilyCard::query()
                    ->where('hospital_id', $hospital->id)
                    ->where('card_number', $validated['family_card_number'])
                    ->first();

                if (! $familyCard) {
                    throw ValidationException::withMessages([
                        'family_card_number' => 'No family card with that number was found.',
                    ]);
                }
            } else {
                do {
                    $cardNumber = 'FC-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
                } while (FamilyCard::where('card_number', $cardNumber)->exists());

                $familyCard = FamilyCard::create([
                    'hospital_id' => $hospital->id,
                    'card_number' => $cardNumber,
                    'family_name' => $validated['family_name'],
                    'contact_name' => $validated['contact_name'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'address' => $validated['address'] ?? null,
                ]);
            }

            $patient->update([
                'family_card_id' => $familyCard->id,
                'family_relationship' => $validated['relationship'] ?? null,
            ]);
        });

        return back()->with('success', 'Patient linked to the family card.');
    }
}
