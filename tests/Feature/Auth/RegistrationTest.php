<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_public_register_route_remains_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_verified_account_request_page_is_public(): void
    {
        $this->get('/create-account')
            ->assertOk()
            ->assertSee('Create your SniperPOS account')
            ->assertSee('Approval required');
    }
}
