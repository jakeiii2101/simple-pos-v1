<?php

namespace App\Livewire\Platform;

use App\Models\Account;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AccountApprovals extends Component
{
    public ?int $rejectingAccountId = null;

    public string $rejectionReason = '';

    public string $statusFilter = 'pending';

    public function boot(): void
    {
        abort_unless(auth()->user()?->isPlatformOwner(), 403);
    }

    public function approve(int $accountId): void
    {
        DB::transaction(function () use ($accountId): void {
            $account = Account::query()->lockForUpdate()->findOrFail($accountId);

            if (! $account->isPending()) {
                return;
            }

            $owner = User::withoutGlobalScope('account')->findOrFail($account->owner_user_id);

            $account->update([
                'status' => Account::STATUS_ACTIVE,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            $owner->status = User::STATUS_ACTIVE;
            $owner->email_verified_at ??= now();
            $owner->save();

            Audit::record(
                'account.approved',
                $account,
                'SniperPOS business account approved: '.$account->name,
                [
                    'account_id' => $account->id,
                    'business_name' => $account->name,
                    'owner_email' => $owner->email,
                ],
            );
        });

        session()->flash('success', 'Business account approved. The owner can now log in.');
    }

    public function beginReject(int $accountId): void
    {
        $account = Account::query()->findOrFail($accountId);

        abort_unless($account->isPending(), 422);

        $this->rejectingAccountId = $account->id;
        $this->rejectionReason = '';
        $this->resetValidation();
    }

    public function reject(): void
    {
        $validated = $this->validate([
            'rejectingAccountId' => ['required', 'integer', Rule::exists('accounts', 'id')],
            'rejectionReason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        DB::transaction(function () use ($validated): void {
            $account = Account::query()->lockForUpdate()->findOrFail($validated['rejectingAccountId']);

            if (! $account->isPending()) {
                return;
            }

            $owner = User::withoutGlobalScope('account')->findOrFail($account->owner_user_id);

            $account->update([
                'status' => Account::STATUS_REJECTED,
                'rejected_at' => now(),
                'rejection_reason' => trim($validated['rejectionReason']),
            ]);

            $owner->status = User::STATUS_INACTIVE;
            $owner->save();

            Audit::record(
                'account.rejected',
                $account,
                'SniperPOS business account rejected: '.$account->name,
                [
                    'account_id' => $account->id,
                    'business_name' => $account->name,
                    'reason' => $account->rejection_reason,
                ],
            );
        });

        $this->cancelReject();
        session()->flash('success', 'Business account request rejected.');
    }

    public function suspend(int $accountId): void
    {
        $account = Account::query()->findOrFail($accountId);

        abort_if($account->id === auth()->user()->account_id, 422, 'You cannot suspend your own platform account.');

        if ($account->status !== Account::STATUS_ACTIVE) {
            return;
        }

        $account->update(['status' => Account::STATUS_SUSPENDED]);

        Audit::record(
            'account.suspended',
            $account,
            'SniperPOS business account suspended: '.$account->name,
            ['account_id' => $account->id, 'business_name' => $account->name],
        );

        session()->flash('success', 'Business account suspended.');
    }

    public function reactivate(int $accountId): void
    {
        $account = Account::query()->findOrFail($accountId);

        if ($account->status !== Account::STATUS_SUSPENDED) {
            return;
        }

        $account->update([
            'status' => Account::STATUS_ACTIVE,
            'approved_by' => auth()->id(),
            'approved_at' => $account->approved_at ?? now(),
        ]);

        Audit::record(
            'account.reactivated',
            $account,
            'SniperPOS business account reactivated: '.$account->name,
            ['account_id' => $account->id, 'business_name' => $account->name],
        );

        session()->flash('success', 'Business account reactivated.');
    }

    public function cancelReject(): void
    {
        $this->rejectingAccountId = null;
        $this->rejectionReason = '';
        $this->resetValidation();
    }

    public function render()
    {
        $query = Account::query()->with(['owner', 'approver'])->latest('id');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.platform.account-approvals', [
            'accounts' => $query->get(),
            'pendingCount' => Account::query()->where('status', Account::STATUS_PENDING)->count(),
        ]);
    }
}
