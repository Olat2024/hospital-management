<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('doctors', function (Blueprint $table) {
            // Scope the doctor to a hospital (nullable so existing rows stay valid).
            if (!Schema::hasColumn('doctors', 'hospital_id')) {
                $table->foreignId('hospital_id')
                    ->nullable()
                    ->constrained('hospitals')
                    ->nullOnDelete()
                    ->after('user_id');
            }

            // Whether the doctor currently accepts new appointments.
            if (!Schema::hasColumn('doctors', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('phone');
            }
        });

        // Backfill: existing doctors belong to the primary hospital.
        $hospitalId = DB::table('hospitals')->orderBy('id')->value('id');
        if ($hospitalId) {
            DB::table('doctors')->whereNull('hospital_id')->update(['hospital_id' => $hospitalId]);
        }
    }

    public function down(): void {
        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasColumn('doctors', 'hospital_id')) {
                $table->dropConstrainedForeignId('hospital_id');
            }
            if (Schema::hasColumn('doctors', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
