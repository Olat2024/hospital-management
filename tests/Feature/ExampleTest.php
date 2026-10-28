<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_welcome_page_uses_hospital_branding_and_portal_actions(): void
    {
        $this->withoutVite()
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('hospitalName', 'CarePoint Hospital')
                ->where('canLogin', true)
                ->where('canRegister', true)
                ->has('tagline')
                ->has('logoUrl'));
    }
}
