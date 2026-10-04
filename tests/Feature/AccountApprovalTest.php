<?php

namespace Tests\Feature;

use App\Livewire\Platform\AccountApprovals;
use App\Models\AccountRequest;
use App\Models\Business;
use App\Models\PrinterSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AccountApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_request_a_separate_sniperpos_business_account(): void
    {
        $this->get('/create-account')
            ->assertOk()
            ->assertSee('Create a SniperPOS account')
            ->assertSeeVolt('pages.auth.create-account');

        Volt::test('pages.auth.create-account')
            ->set('businessName', 'JKH Mini Mart')
            ->set('ownerName', 'Juan Owner')
            ->set('email', 'owner@example.com')
            ->set('password', 'StrongPassword123!')
            ->set('password_confirmation', 'StrongPassword123!')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $request = AccountRequest::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->assertSame(AccountRequest::STATUS_PENDING, $request->status);
        $this->assertTrue(Hash::check('StrongPassword123!', $request->password_hash));
        $this->assertDatabaseMissing('users', ['email' => 'owner@example.com']);
    }

    public function test_only_platform_owner_can_open_account_approvals(): void
    {
        $regularAdmin = User::factory()->admin()->create([
            'is_platform_owner' => false,
        ]);

        $this->actingAs($regularAdmin)
            ->get('/platform/account-approvals')
            ->assertForbidden();

        $platformOwner = User::factory()->admin()->create([
            'is_platform_owner' => true,
        ]);

        $this->actingAs($platformOwner)
            ->get('/platform/account-approvals')
            ->assertOk()
            ->assertSee('Account Approvals');
    }

    public function test_platform_owner_approval_creates_isolated_business_admin_account(): void
    {
        $platformOwner = User::factory()->admin()->create([
            'is_platform_owner' => true,
        ]);

        $request = AccountRequest::query()->create([
            'business_name' => 'Approved Store',
            'owner_name' => 'Approved Owner',
            'email' => 'approved@example.com',
            'password_hash' => Hash::make('ApprovedPassword123!'),
            'status' => AccountRequest::STATUS_PENDING,
        ]);

        Livewire::actingAs($platformOwner)
            ->test(AccountApprovals::class)
            ->call('approve', $request->id)
            ->assertHasNoErrors();

        $business = Business::query()->where('name', 'Approved Store')->firstOrFail();
        $user = User::query()->where('email', 'approved@example.com')->firstOrFail();

        $this->assertSame($business->id, $user->business_id);
        $this->assertSame(User::ROLE_ADMIN, $user->role);
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertFalse($user->isPlatformOwner());
        $this->assertTrue(Hash::check('ApprovedPassword123!', $user->password));

        $request->refresh();
        $this->assertSame(AccountRequest::STATUS_APPROVED, $request->status);
        $this->assertNull($request->password_hash);
        $this->assertSame($platformOwner->id, $request->approved_by);

        $this->assertDatabaseHas('printer_settings', [
            'business_id' => $business->id,
            'paper_width_mm' => PrinterSetting::DEFAULT_PAPER_WIDTH_MM,
        ]);

        Volt::test('pages.auth.login')
            ->set('form.email', 'approved@example.com')
            ->set('form.password', 'ApprovedPassword123!')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_platform_owner_command_keeps_only_one_account_approver(): void
    {
        $first = User::factory()->admin()->create([
            'email' => 'first-owner@example.com',
            'is_platform_owner' => true,
        ]);
        $second = User::factory()->admin()->create([
            'email' => 'second-owner@example.com',
            'is_platform_owner' => false,
        ]);

        $exitCode = Artisan::call('sniperpos:make-platform-owner', [
            'email' => $second->email,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertFalse($first->fresh()->isPlatformOwner());
        $this->assertTrue($second->fresh()->isPlatformOwner());
    }
}
