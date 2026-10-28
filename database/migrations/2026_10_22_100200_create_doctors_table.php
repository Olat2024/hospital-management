<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            // Add the user_id foreign key to link login credentials to the doctor profile
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('specialization');
            $table->string('phone');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('doctors');
    }
};