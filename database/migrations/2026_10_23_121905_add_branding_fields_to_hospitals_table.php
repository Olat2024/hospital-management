<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            // logo already exists from 2026_10_23_000001_add_logo_to_hospitals_table
            if (! Schema::hasColumn('hospitals', 'favicon')) {
                $table->string('favicon')->nullable()->after('logo');
            }
            if (! Schema::hasColumn('hospitals', 'primary_color')) {
                $table->string('primary_color', 7)->nullable()->after('favicon');
            }
            if (! Schema::hasColumn('hospitals', 'secondary_color')) {
                $table->string('secondary_color', 7)->nullable()->after('primary_color');
            }
            if (! Schema::hasColumn('hospitals', 'tagline')) {
                $table->string('tagline')->nullable()->after('secondary_color');
            }
            if (! Schema::hasColumn('hospitals', 'address')) {
                $table->text('address')->nullable()->after('tagline');
            }
            if (! Schema::hasColumn('hospitals', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            if (! Schema::hasColumn('hospitals', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (! Schema::hasColumn('hospitals', 'country')) {
                $table->string('country')->default('Nigeria')->after('state');
            }
            if (! Schema::hasColumn('hospitals', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('country');
            }
            if (! Schema::hasColumn('hospitals', 'phone')) {
                $table->string('phone')->nullable()->after('postal_code');
            }
            if (! Schema::hasColumn('hospitals', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('hospitals', 'website')) {
                $table->string('website')->nullable()->after('email');
            }
            if (! Schema::hasColumn('hospitals', 'social_links')) {
                $table->json('social_links')->nullable()->after('website');
            }
            if (! Schema::hasColumn('hospitals', 'footer_text')) {
                $table->text('footer_text')->nullable()->after('social_links');
            }
            if (! Schema::hasColumn('hospitals', 'timezone')) {
                $table->string('timezone', 50)->default('Africa/Lagos')->after('footer_text');
            }
            if (! Schema::hasColumn('hospitals', 'currency')) {
                $table->string('currency', 3)->default('NGN')->after('timezone');
            }
            if (! Schema::hasColumn('hospitals', 'locale')) {
                $table->string('locale', 5)->default('en')->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $columnsToDrop = [
                'favicon', 'primary_color', 'secondary_color',
                'tagline', 'address', 'city', 'state', 'country',
                'postal_code', 'phone', 'email', 'website',
                'social_links', 'footer_text', 'timezone', 'currency', 'locale',
            ];
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('hospitals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};