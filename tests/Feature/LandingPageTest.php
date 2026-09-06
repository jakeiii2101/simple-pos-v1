<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_sniperpos_landing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('SniperPOS')
            ->assertSee('Precision in')
            ->assertSee('Every Sale.')
            ->assertSee('Get Started')
            ->assertSee(route('login'), false);
    }

    public function test_authenticated_user_sees_application_actions(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Open POS');
    }
}
