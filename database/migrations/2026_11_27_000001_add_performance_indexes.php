<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->index(['hospital_id', 'last_name', 'first_name'], 'patients_hospital_name_idx');
            $table->index(['hospital_id', 'phone'], 'patients_hospital_phone_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['hospital_id', 'issued_at'], 'invoices_hospital_issued_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('paid_at', 'payments_paid_at_idx');
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->index(['patient_id', 'created_at'], 'medical_records_patient_created_idx');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['hospital_id', 'scheduled_at'], 'appointments_hospital_scheduled_idx');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_hospital_scheduled_idx');
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropIndex('medical_records_patient_created_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_paid_at_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_hospital_issued_idx');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('patients_hospital_phone_idx');
            $table->dropIndex('patients_hospital_name_idx');
        });
    }
};
