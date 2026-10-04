<div class="sniper-page">
    <div class="sniper-page-header">
        <div>
            <div class="sniper-kicker">Platform Owner</div>
            <h1 class="sniper-title mt-1">Account Approvals</h1>
            <p class="sniper-subtitle">Only the SniperPOS platform owner can approve new business workspaces.</p>
        </div>
        <span class="sniper-badge-navy">{{ $pendingCount }} pending</span>
    </div>

    @if (session('success'))
        <div class="sniper-alert-success mb-5">{{ session('success') }}</div>
    @endif

    <div class="mb-5 flex flex-wrap gap-2">
        @foreach (['pending' => 'Pending', 'active' => 'Active', 'suspended' => 'Suspended', 'rejected' => 'Rejected', 'all' => 'All'] as $value => $label)
            <button
                type="button"
                wire:click="$set('statusFilter', '{{ $value }}')"
                class="{{ $statusFilter === $value ? 'sniper-btn-primary' : 'sniper-btn-secondary' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($rejectingAccountId)
        <section class="sniper-form-panel mb-6">
            <div class="sniper-kicker">Reject Request</div>
            <h2 class="mt-1 font-heading text-lg font-bold text-sniper-navy">Reason for rejection</h2>
            <p class="mt-1 text-sm text-sniper-slate">The account will remain locked. You can keep the reason for your administrative record.</p>
            <textarea wire:model="rejectionReason" rows="3" maxlength="500" class="sniper-input mt-4" placeholder="Enter the reason..."></textarea>
            <x-input-error :messages="$errors->get('rejectionReason')" class="mt-2" />
            <div class="mt-4 flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="cancelReject">Cancel</x-secondary-button>
                <button type="button" wire:click="reject" class="sniper-btn-primary !bg-red-700">Reject Account</button>
            </div>
        </section>
    @endif

    <div class="sniper-table-wrap">
        <table class="sniper-table">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Owner</th>
                    <th>Requested</th>
                    <th>Status</th>
                    <th>Approved By</th>
                    <th class="!text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($accounts as $account)
                    <tr wire:key="account-{{ $account->id }}">
                        <td>
                            <div class="font-semibold !text-sniper-navy">{{ $account->name }}</div>
                            <div class="mt-1 text-xs text-sniper-slate">{{ $account->slug }}</div>
                        </td>
                        <td>
                            <div>{{ $account->owner?->name ?? '—' }}</div>
                            <div class="mt-1 text-xs text-sniper-slate">{{ $account->owner?->email ?? '—' }}</div>
                            @if ($account->owner?->phone)
                                <div class="mt-1 text-xs text-sniper-slate">{{ $account->owner->phone }}</div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap">{{ $account->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <span @class([
                                'sniper-badge-success' => $account->status === 'active',
                                'sniper-badge-danger' => in_array($account->status, ['rejected', 'suspended'], true),
                                'sniper-badge-neutral' => $account->status === 'pending',
                            ])>{{ ucfirst($account->status) }}</span>
                            @if ($account->rejection_reason)
                                <div class="mt-2 max-w-xs text-xs text-red-700">{{ $account->rejection_reason }}</div>
                            @endif
                        </td>
                        <td>{{ $account->approver?->name ?? '—' }}</td>
                        <td class="!text-right whitespace-nowrap">
                            @if ($account->status === 'pending')
                                <button type="button" wire:click="approve({{ $account->id }})" wire:confirm="Approve this SniperPOS business account?" class="sniper-action-link">Approve</button>
                                <button type="button" wire:click="beginReject({{ $account->id }})" class="sniper-action-danger ml-4">Reject</button>
                            @elseif ($account->status === 'active' && $account->id !== auth()->user()->account_id)
                                <button type="button" wire:click="suspend({{ $account->id }})" wire:confirm="Suspend this account and block access?" class="sniper-action-danger">Suspend</button>
                            @elseif ($account->status === 'suspended')
                                <button type="button" wire:click="reactivate({{ $account->id }})" wire:confirm="Reactivate this account?" class="sniper-action-link">Reactivate</button>
                            @else
                                <span class="text-xs text-sniper-slate">No action</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="sniper-empty">No account requests match this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
