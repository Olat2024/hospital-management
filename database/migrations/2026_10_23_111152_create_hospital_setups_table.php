<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_setups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            
            // Branding
            $table->string('logo')->nullable();           // stored path: hospital-setups/logo.png
            $table->string('favicon')->nullable();        // stored path: hospital-setups/favicon.ico
            $table->string('primary_color', 7)->nullable(); // hex #RRGGBB
            $table->string('secondary_color', 7)->nullable();
            
            // Contact / Address
            $table->string('name')->nullable();           // override hospital name for display
            $table->string('tagline')->nullable();        // "Your Health, Our Priority"
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('Nigeria');
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            
            // Social / Meta
            $table->json('social_links')->nullable();     // {facebook, twitter, linkedin, instagram}
            $table->text('footer_text')->nullable();      // copyright, disclaimer
            
            // Settings
            $table->boolean('is_active')->default(true);
            $table->string('timezone', 50)->default('Africa/Lagos');
            $table->string('currency', 3)->default('NGN');
            $table->string('locale', 5)->default('en');
            
            $table->timestamps();
            
            $table->index(['hospital_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_setups');
    }
};