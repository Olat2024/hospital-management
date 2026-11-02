<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->default(0)->after('dosage_form');
        });

        Schema::table('pharmacy_dispensation_items', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->default(0)->after('quantity');
            $table->decimal('amount', 12, 2)->default(0)->after('unit_price');
        });

        Schema::table('pharmacy_dispensations', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->unique()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_dispensations', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropUnique(['invoice_id']);
            $table->dropColumn('invoice_id');
        });

        Schema::table('pharmacy_dispensation_items', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'amount']);
        });

        Schema::table('medicines', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });
    }
};
