<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('pregnancy_status')->nullable();
            $table->boolean('is_ems_patient')->default(false);
            $table->text('pregnancy_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['pregnancy_status', 'is_ems_patient', 'pregnancy_notes']);
        });
    }
};
