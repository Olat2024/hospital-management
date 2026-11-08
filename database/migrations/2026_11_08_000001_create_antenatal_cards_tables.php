<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antenatal_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('booking_date')->nullable();
            $table->string('pregnancy_duration_at_booking')->nullable();
            $table->unsignedTinyInteger('age_at_booking')->nullable();
            $table->unsignedTinyInteger('age_at_marriage')->nullable();
            $table->date('last_menstrual_period')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->string('tribe')->nullable();
            $table->string('occupation')->nullable();
            $table->text('special_points')->nullable();
            $table->json('previous_medical_history')->nullable();
            $table->json('previous_pregnancies')->nullable();
            $table->unsignedSmallInteger('previous_pregnancies_total')->nullable();
            $table->unsignedSmallInteger('living_children')->nullable();
            $table->timestamps();
            $table->index(['hospital_id', 'booking_date']);
            $table->index(['patient_id', 'booking_date']);
        });

        Schema::create('antenatal_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('antenatal_card_id')->constrained()->cascadeOnDelete();
            $table->date('visit_date');
            $table->decimal('fundal_height_cm', 5, 2)->nullable();
            $table->string('presentation_position')->nullable();
            $table->string('presenting_part_relation')->nullable();
            $table->string('foetal_heart')->nullable();
            $table->string('urine')->nullable();
            $table->string('blood_pressure')->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->decimal('pcv', 5, 2)->nullable();
            $table->string('oedema')->nullable();
            $table->text('remarks')->nullable();
            $table->string('examiner_initials')->nullable();
            $table->date('return_date')->nullable();
            $table->timestamps();
            $table->index(['antenatal_card_id', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antenatal_visits');
        Schema::dropIfExists('antenatal_cards');
    }
};
