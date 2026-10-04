<div class="sniper-page">
    <div class="sniper-page-header">
        <div>
            <div class="sniper-kicker">Platform Owner</div>
            <h1 class="sniper-title mt-1">Account Approvals</h1>
            <p class="sniper-subtitle">Review every customer request before a separate SniperPOS business workspace is activated.</p>
        </div>
        <span class="sniper-badge-navy">{{ $pendingRequests->count() }} pending</span>
    </div>

    @if (session('success'))
        <div class="sniper-alert-success mb-5">{{ session('success') }}</div>
    @endif

    @error('approval')
        <div class="sniper-alert-danger mb-5">{{ $message }}</div>
    @enderror

    <section class="sniper-card overflow-hidden">
        <div class="sniper-section-header">
            <h2 class="font-heading text-base font-bold text-sniper-navy">Pending Requests</h2>
            <p class="mt-1 text-xs text-sniper-slate">Approving creates a new isolated business workspace and makes the requester its administrator.</p>
        </div>

        @forelse ($pendingRequests as $request)
            <div class="border-t border-slate-200 p-5 first:border-t-0 sm:p-6" wire:key="account-request-{{ $request->id }}">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="font-heading text-lg font-bold text-sniper-navy">{{ $request->business_name }}</div>
                        <div class="mt-2 grid gap-x-8 gap-y-2 text-sm text-sniper-slate sm:grid-cols-2">
                            <div><span class="font-semibold text-sniper-navy">Owner:</span> {{ $request->owner_name }}</div>
                            <div><span class="font-semibold text-sniper-navy">Email:</span> {{ $request->email }}</div>
                            <div><span class="font-semibold text-sniper-navy">Submitted:</span> {{ $request->created_at->format('M d, Y g:i A') }}</div>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap gap-2">
                        <button type="button" wire:click="approve({{ $request->id }})" wire:confirm="Approve this account and create a separate SniperPOS workspace?" class="sniper-btn-primary">
                            Approve Account
                        </button>
                        <button type="button" wire:click="startReject({{ $request->id }})" class="sniper-btn-secondary">
                            Reject
                        </button>
                    </div>
                </div>

                @if ($rejectingId === $request->id)
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4">
                        <label for="rejection-reason-{{ $request->id }}" class="sniper-label">Reason for rejection</label>
                        <textarea id="rejection-reason-{{ $request->id }}" wire:model="rejectionReason" rows="3" maxlength="500" class="sniper-input mt-1.5" placeholder="Enter a short reason"></textarea>
                        <x-input-error :messages="$errors->get('rejectionReason')" class="mt-2" />
                        <div class="mt-3 flex justify-end gap-2">
                            <button type="button" wire:click="cancelReject" class="sniper-btn-secondary">Cancel</button>
                            <button type="button" wire:click="reject" class="sniper-btn-primary">Confirm Rejection</button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="sniper-empty">No account requests are waiting for approval.</div>
        @endforelse
    </section>

    <section class="sniper-card mt-6 overflow-hidden">
        <div class="sniper-section-header">
            <h2 class="font-heading text-base font-bold text-sniper-navy">Recent Decisions</h2>
            <p class="mt-1 text-xs text-sniper-slate">Latest approved and rejected account requests.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="sniper-table">
                <thead>
                    <tr><th>Business</th><th>Owner</th><th>Email</th><th>Status</th><th>Processed</th></tr>
                </thead>
                <tbody>
                    @forelse ($recentRequests as $request)
                        <tr>
                            <td class="font-semibold !text-sniper-navy">{{ $request->business_name }}</td>
                            <td>{{ $request->owner_name }}</td>
                            <td>{{ $request->email }}</td>
                            <td>
                                <span class="{{ $request->status === 'approved' ? 'sniper-badge-success' : 'sniper-badge-danger' }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                                @if ($request->rejection_reason)
                                    <div class="mt-1 max-w-xs text-xs text-sniper-slate">{{ $request->rejection_reason }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-xs text-sniper-slate">
                                {{ optional($request->approved_at ?? $request->rejected_at)->format('M d, Y g:i A') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="sniper-empty">No processed account requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
