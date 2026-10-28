<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('generic_name')->nullable();
            $table->string('strength')->nullable();
            $table->string('dosage_form')->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('reorder_level')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['hospital_id', 'name']);
        });

        Schema::create('pharmacy_dispensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained()->restrictOnDelete();
            $table->foreignId('dispensed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('dispensed_at');
            $table->timestamps();
            $table->index(['medical_record_id', 'dispensed_at']);
        });

        Schema::create('pharmacy_dispensation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispensation_id')->constrained('pharmacy_dispensations')->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });

        Schema::create('pharmacy_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dispensation_id')->nullable()->constrained('pharmacy_dispensations')->nullOnDelete();
            $table->string('type');
            $table->unsignedInteger('quantity');
            $table->string('reference')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['medicine_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stock_movements');
        Schema::dropIfExists('pharmacy_dispensation_items');
        Schema::dropIfExists('pharmacy_dispensations');
        Schema::dropIfExists('medicines');
    }
};
