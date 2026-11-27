<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\HospitalTestSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LabWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $labTech;

    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(HospitalTestSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->labTech = User::factory()->create();
        $this->labTech->assignRole('Lab Technician');

        $this->patient = Patient::create([
            'hospital_id' => Hospital::default()->id,
            'mrn' => 'MRN-LAB-00001',
            'first_name' => 'Ibrahim',
            'last_name' => 'Sani',
            'dob' => '1991-06-15',
            'gender' => 'Male',
            'phone' => '08040000001',
            'address' => '10 Lab Road',
        ]);
    }

    public function test_lab_technician_can_order_test_and_record_results(): void
    {
        $this->actingAs($this->labTech)
            ->get(route('lab.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Lab/Index')
                ->where('canManage', true)
                ->where('summary.total', 0));

        $this->actingAs($this->labTech)
            ->post(route('lab.store'), [
                'patient_id' => $this->patient->id,
                'test_name' => 'Full Blood Count',
                'category' => 'Hematology',
                'priority' => 'urgent',
                'notes' => 'Rule out anemia',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $order = LabOrder::firstOrFail();
        $this->assertSame('pending', $order->status);

        $this->actingAs($this->labTech)
            ->patch(route('lab.update', $order), [
                'status' => 'completed',
                'result_summary' => 'Hb 13.1 g/dL (Normal)',
                'result_notes' => 'All indices within reference range.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertSame('Hb 13.1 g/dL (Normal)', $order->result_summary);
        $this->assertNotNull($order->completed_at);
    }

    public function test_receptionist_cannot_access_laboratory_queue(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole('Receptionist');

        $this->actingAs($receptionist)->get(route('lab.index'))->assertForbidden();
        $this->actingAs($receptionist)->post(route('lab.store'), [])->assertForbidden();
    }
}
