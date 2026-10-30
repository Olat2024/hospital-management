<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->string('card_number')->unique();
            $table->string('family_name');
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
            $table->index(['hospital_id', 'family_name']);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('family_card_id')->nullable()->after('hospital_id')
                ->constrained('family_cards')->nullOnDelete();
            $table->string('family_relationship')->nullable()->after('family_card_id');
        });

        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('discharged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('admitted');
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->text('discharge_notes')->nullable();
            $table->timestamp('admitted_at');
            $table->timestamp('discharged_at')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'status']);
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('referred_to');
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->timestamp('referred_at');
            $table->timestamps();
            $table->index(['patient_id', 'referred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('admissions');

        Schema::table('patients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('family_card_id');
            $table->dropColumn('family_relationship');
        });

        Schema::dropIfExists('family_cards');
    }
};
