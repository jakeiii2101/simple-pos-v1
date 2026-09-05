<?php

namespace Tests\Feature;

use App\Livewire\Users\UserManagement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_users_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/users')
            ->assertOk();
    }

    public function test_cashier_cannot_access_users_page(): void
    {
        $cashier = User::factory()->create();

        $this->actingAs($cashier)
            ->get('/users')
            ->assertForbidden();
    }

    public function test_admin_can_create_cashier(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(UserManagement::class)
            ->call('create')
            ->set('name', 'Cashier One')
            ->set('email', 'cashier1@example.com')
            ->set('password', 'password123')
            ->set('passwordConfirmation', 'password123')
            ->set('role', User::ROLE_CASHIER)
            ->set('status', User::STATUS_ACTIVE)
            ->call('save')
            ->assertHasNoErrors();

        $user = User::query()->where('email', 'cashier1@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_CASHIER, $user->role);
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(UserManagement::class)
            ->call('edit', $admin->id)
            ->set('status', User::STATUS_INACTIVE)
            ->call('save')
            ->assertHasErrors('status');

        $this->assertSame(User::STATUS_ACTIVE, $admin->fresh()->status);
    }

    public function test_admin_cannot_remove_own_admin_role(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(UserManagement::class)
            ->call('edit', $admin->id)
            ->set('role', User::ROLE_CASHIER)
            ->call('save')
            ->assertHasErrors('role');

        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
    }
}
