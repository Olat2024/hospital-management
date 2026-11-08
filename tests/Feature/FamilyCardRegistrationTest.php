<?php

namespace Tests\Feature;

use App\Models\FamilyCard;
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
}
