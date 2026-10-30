<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\User;
use Database\Seeders\HospitalSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HospitalSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_hospital_seeder_creates_a_default_hospital_and_allows_staff_dashboard(): void
    {
        $this->seed(PermissionSeeder::class);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('Admin');

        $this->seed(HospitalSeeder::class);

        $this->assertDatabaseHas('hospitals', [
            'name' => 'Siloam Grace Clinic & Maternity Home',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_staff_dashboard_creates_default_hospital_automatically_when_database_is_empty(): void
    {
        $this->seed(PermissionSeeder::class);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('Admin');

        $this->assertDatabaseCount('hospitals', 0);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertDatabaseHas('hospitals', [
            'name' => 'Siloam Grace Clinic & Maternity Home',
            'is_active' => true,
        ]);
    }

    public function test_hospital_seeder_is_idempotent_when_an_active_hospital_exists(): void
    {
        $existing = Hospital::create([
            'name' => 'Existing Clinic',
            'is_active' => true,
        ]);

        $this->seed(HospitalSeeder::class);

        $this->assertDatabaseCount('hospitals', 1);
        $this->assertSame($existing->id, Hospital::default()->id);
    }

    public function test_hospital_seeder_activates_existing_hospital_if_none_is_active(): void
    {
        $existing = Hospital::create([
            'name' => 'Existing Clinic',
            'is_active' => false,
        ]);

        $this->seed(HospitalSeeder::class);

        $this->assertDatabaseCount('hospitals', 1);
        $this->assertTrue($existing->fresh()->is_active);
    }
}
