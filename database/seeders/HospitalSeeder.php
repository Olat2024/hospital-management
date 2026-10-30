<?php

namespace Database\Seeders;

use App\Models\Hospital;
use Illuminate\Database\Seeder;

class HospitalSeeder extends Seeder
{
    public function run(): void
    {
        if (Hospital::query()->where('is_active', true)->exists()) {
            return;
        }

        $hospital = Hospital::query()->first();

        if ($hospital) {
            $hospital->update(['is_active' => true]);

            return;
        }

        Hospital::create([
            'name' => 'Siloam Grace Clinic & Maternity Home',
            'is_active' => true,
        ]);
    }
}
