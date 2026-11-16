<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->foreignId('doctor_id')->nullable()->change();
            $table->foreign('doctor_id')->references('id')->on('doctors')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->after('doctor_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropForeign(['doctor_id']);
            $table->foreignId('doctor_id')->nullable(false)->change();
            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
        });
    }
};
