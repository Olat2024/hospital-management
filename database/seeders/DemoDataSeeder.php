<?php
namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo patients and appointments so the dashboards and patient pages have
 * something to render right after a fresh seed.
 */
class DemoDataSeeder extends Seeder {
    public function run(): void {
        if (Patient::query()->exists()) {
            return;
        }

        $hospital = Hospital::default();
        $doctor = Doctor::query()->first();

        $patients = Patient::create([
            'hospital_id' => $hospital->id,
            'mrn' => 'MRN-261023-00001',
            'first_name' => 'Adebayo',
            'last_name' => 'Ogunleye',
            'dob' => '1988-04-12',
            'gender' => 'Male',
            'blood_group' => 'O+',
            'phone' => '08031234567',
            'email' => 'adebayo.ogunleye@example.com',
            'address' => '14 Oba Avenue, Ijebu Ode, Ogun State',
            'allergies' => 'Penicillin',
            'emergency_contact_name' => 'Funmi Ogunleye',
            'emergency_contact_phone' => '08037654321',
        ]);

        Patient::create([
            'hospital_id' => $hospital->id,
            'mrn' => 'MRN-261023-00002',
            'first_name' => 'Chinwe',
            'last_name' => 'Okonkwo',
            'dob' => '1995-11-03',
            'gender' => 'Female',
            'blood_group' => 'A+',
            'phone' => '08055551234',
            'email' => 'chinwe.okonkwo@example.com',
            'address' => '7 Ekotedo Street, Ijebu Ode, Ogun State',
            'insurance_provider' => 'NHIS',
            'insurance_number' => 'NHIS-998877',
        ]);

        Patient::create([
            'hospital_id' => $hospital->id,
            'mrn' => 'MRN-261023-00003',
            'first_name' => 'Tunde',
            'last_name' => 'Bakare',
            'dob' => '1976-01-27',
            'gender' => 'Male',
            'blood_group' => 'B-',
            'phone' => '08077778888',
            'address' => '22 Waanba Street, Ijebu Ode, Ogun State',
            'allergies' => 'None recorded',
        ]);

        if ($doctor) {
            Appointment::create([
                'hospital_id' => $hospital->id,
                'patient_id' => $patients->id,
                'doctor_id' => $doctor->id,
                'scheduled_at' => Carbon::today()->setTime(9, 30),
                'status' => 'scheduled',
                'type' => 'opd',
                'reason' => 'Routine check-up',
            ]);

            Appointment::create([
                'hospital_id' => $hospital->id,
                'patient_id' => $patients->id,
                'doctor_id' => $doctor->id,
                'scheduled_at' => Carbon::tomorrow()->setTime(11, 0),
                'status' => 'scheduled',
                'type' => 'opd',
                'reason' => 'Follow-up consultation',
            ]);
        }
    }
}
