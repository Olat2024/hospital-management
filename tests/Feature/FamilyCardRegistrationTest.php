<?php

namespace Tests\Feature;

use App\Models\FamilyCard;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\HospitalTestSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FamilyCardRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->seed(HospitalTestSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_receptionist_can_create_a_family_card_separately(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole('Receptionist');

        $response = $this->actingAs($receptionist)->post(route('family-cards.store'), [
            'family_name' => 'Adebayo',
            'contact_name' => 'Tola Adebayo',
            'phone' => '08012345678',
            'address' => 'Ijebu Ode',
        ]);

        $card = FamilyCard::firstOrFail();
        $response->assertRedirect(route('family-cards.index'));
        $this->assertSame('Adebayo', $card->family_name);
        $this->assertStringStartsWith('FC-', $card->card_number);
    }

    public function test_family_card_directory_lists_cards_and_members(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole('Receptionist');

        $this->actingAs($receptionist)
            ->get(route('family-cards.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('FamilyCards/Index'));
    }

    public function test_receptionist_can_edit_family_card_details_without_changing_card_number_or_members(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole('Receptionist');
        $hospital = Hospital::default();
        $card = FamilyCard::create([
            'hospital_id' => $hospital->id,
            'card_number' => 'FC-TEST-000001',
            'family_name' => 'Adebayo',
            'contact_name' => 'Tola Adebayo',
            'phone' => '08012345678',
            'address' => 'Old address',
        ]);
        $patient = Patient::create([
            'hospital_id' => $hospital->id,
            'family_card_id' => $card->id,
            'family_relationship' => 'Child',
            'mrn' => 'MRN-FAMILY-CARD-0001',
            'first_name' => 'Bola',
            'last_name' => 'Adebayo',
            'dob' => '2010-01-01',
            'gender' => 'Female',
            'phone' => '08011112222',
            'address' => 'Old address',
        ]);

        $this->actingAs($receptionist)
            ->get(route('family-cards.edit', $card))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('FamilyCards/Create')
                ->where('card.card_number', 'FC-TEST-000001')
                ->where('card.family_name', 'Adebayo'));

        $this->actingAs($receptionist)
            ->put(route('family-cards.update', $card), [
                'family_name' => 'Adebayo-Ojo',
                'contact_name' => 'Tola Ojo',
                'phone' => '08098765432',
                'address' => 'New address',
            ])
            ->assertRedirect(route('family-cards.index'))
            ->assertSessionHas('success');

        $card->refresh();
        $this->assertSame('FC-TEST-000001', $card->card_number);
        $this->assertSame('Adebayo-Ojo', $card->family_name);
        $this->assertSame('Tola Ojo', $card->contact_name);
        $this->assertSame('08098765432', $card->phone);
        $this->assertSame('New address', $card->address);
        $this->assertSame($card->id, $patient->fresh()->family_card_id);
    }

    public function test_family_card_edit_is_scoped_to_the_active_hospital(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole('Receptionist');
        $otherHospital = Hospital::create(['name' => 'Another Hospital']);
        $card = FamilyCard::create([
            'hospital_id' => $otherHospital->id,
            'card_number' => 'FC-OTHER-000001',
            'family_name' => 'Other',
        ]);

        $this->actingAs($receptionist)
            ->get(route('family-cards.edit', $card))
            ->assertNotFound();

        $this->actingAs($receptionist)
            ->put(route('family-cards.update', $card), [
                'family_name' => 'Changed',
            ])
            ->assertNotFound();

        $this->assertSame('Other', $card->fresh()->family_name);
    }
}
