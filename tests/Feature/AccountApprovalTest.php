<?php

namespace Tests\Feature;

use App\Livewire\Auth\CreateAccount;
use App\Livewire\Platform\AccountApprovals;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AccountApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_can_request_account_but_cannot_login_before_approval(): void
    {
        Livewire::test(CreateAccount::class)
            ->set('businessName', 'North Star Grocery')
            ->set('ownerName', 'Maria Santos')
            ->set('email', 'owner@northstar.test')
            ->set('phone', '09171234567')
            ->set('password', 'SecurePass123')
            ->set('passwordConfirmation', 'SecurePass123')
            ->set('termsAccepted', true)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('login', absolute: false));

        $account = Account::query()->where('name', 'North Star Grocery')->firstOrFail();
        $owner = User::withoutGlobalScope('account')
            ->where('email', 'owner@northstar.test')
            ->firstOrFail();

        $this->assertSame(Account::STATUS_PENDING, $account->status);
        $this->assertSame($account->id, $owner->account_id);
        $this->assertSame(User::ROLE_ADMIN, $owner->role);
        $this->assertSame(User::STATUS_PENDING, $owner->status);
        $this->assertTrue(Hash::check('SecurePass123', $owner->password));

        $this->assertDatabaseHas('audit_logs', [
            'account_id' => $account->id,
            'user_id' => $owner->id,
            'action' => 'account.application_submitted',
        ]);

        Volt::test('pages.auth.login')
            ->set('form.email', 'owner@northstar.test')
            ->set('form.password', 'SecurePass123')
            ->call('login')
            ->assertHasErrors('form.email')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_only_platform_owner_can_review_and_approve_account_requests(): void
    {
        $platformOwner = User::factory()->admin()->create([
            'is_platform_owner' => true,
        ]);
        $regularAdmin = User::factory()->admin()->create();

        $this->actingAs($regularAdmin)
            ->get('/platform/accounts')
            ->assertForbidden();

        $this->actingAs($platformOwner)
            ->get('/platform/accounts')
            ->assertOk()
            ->assertSee('Account Approvals');

        Auth::logout();

        Livewire::test(CreateAccount::class)
            ->set('businessName', 'Approved Retail')
            ->set('ownerName', 'Approved Owner')
            ->set('email', 'approved@example.test')
            ->set('password', 'Approval1234')
            ->set('passwordConfirmation', 'Approval1234')
            ->set('termsAccepted', true)
            ->call('submit')
            ->assertHasNoErrors();

        $account = Account::query()->where('name', 'Approved Retail')->firstOrFail();

        Livewire::actingAs($platformOwner)
            ->test(AccountApprovals::class)
            ->call('approve', $account->id)
            ->assertHasNoErrors();

        $account->refresh();
        $owner = User::withoutGlobalScope('account')
            ->where('email', 'approved@example.test')
            ->firstOrFail();

        $this->assertSame(Account::STATUS_ACTIVE, $account->status);
        $this->assertSame($platformOwner->id, $account->approved_by);
        $this->assertNotNull($account->approved_at);
        $this->assertSame(User::STATUS_ACTIVE, $owner->status);

        Auth::logout();

        Volt::test('pages.auth.login')
            ->set('form.email', $owner->email)
            ->set('form.password', 'Approval1234')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($owner);
    }

    public function test_rejected_or_suspended_business_account_cannot_use_pos_workspace(): void
    {
        $platformOwner = User::factory()->admin()->create([
            'is_platform_owner' => true,
        ]);

        $account = Account::query()->create([
            'name' => 'Suspended Store',
            'slug' => 'suspended-store',
            'status' => Account::STATUS_ACTIVE,
        ]);

        $owner = User::factory()->admin()->create([
            'account_id' => $account->id,
            'email' => 'suspended@example.test',
        ]);
        $account->update(['owner_user_id' => $owner->id]);

        Livewire::actingAs($platformOwner)
            ->test(AccountApprovals::class)
            ->call('suspend', $account->id)
            ->assertHasNoErrors();

        $this->assertSame(Account::STATUS_SUSPENDED, $account->fresh()->status);

        $this->actingAs($owner)
            ->get('/dashboard')
            ->assertForbidden();

        Auth::logout();

        Volt::test('pages.auth.login')
            ->set('form.email', $owner->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors('form.email')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_platform_owner_command_assigns_approval_power_to_exactly_one_trusted_user(): void
    {
        $oldOwner = User::factory()->admin()->create([
            'email' => 'old-owner@example.test',
            'is_platform_owner' => true,
        ]);
        $trustedOwner = User::factory()->admin()->create([
            'email' => 'trusted-owner@example.test',
            'is_platform_owner' => false,
        ]);

        $this->artisan('platform:owner', ['email' => $trustedOwner->email])
            ->assertSuccessful();

        $this->assertFalse((bool) User::withoutGlobalScope('account')->findOrFail($oldOwner->id)->is_platform_owner);
        $this->assertTrue((bool) User::withoutGlobalScope('account')->findOrFail($trustedOwner->id)->is_platform_owner);
        $this->assertSame(
            1,
            User::withoutGlobalScope('account')->where('is_platform_owner', true)->count()
        );
    }
}
