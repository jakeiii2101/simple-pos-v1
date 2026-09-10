<div class="sniper-card p-5">
    <div class="sniper-kicker">Controlled reversal</div>

    @if (session('adjustment-success'))
        <div class="sniper-alert-success mt-4">{{ session('adjustment-success') }}</div>
    @endif

    @if ($sale->adjustment)
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
            <div class="font-heading text-base font-bold">{{ $sale->adjustment->type === 'void' ? 'VOIDED' : 'REFUNDED' }}</div>
            <div class="mt-2">₱{{ number_format((float) $sale->adjustment->amount, 2) }} · {{ $sale->adjustment->processed_at->format('M d, Y g:i A') }}</div>
            <div class="mt-1">Authorized by {{ $sale->adjustment->authorizedBy->name }}</div>
            <div class="mt-2 text-xs leading-5">Reason: {{ $sale->adjustment->reason }}</div>
            <div class="mt-1 text-xs">Inventory {{ $sale->adjustment->inventory_restocked ? 'was restored' : 'was not restocked' }}.</div>
        </div>
    @else
        <p class="mt-2 text-xs leading-5 text-sniper-slate">The original Sales Invoice remains unchanged. This creates a separate audit record and, when selected, restores inventory.</p>

        @error('reversal')<div class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</div>@enderror

        <div class="mt-4 space-y-4">
            <div><x-input-label for="reversal-type" value="Action" /><select id="reversal-type" wire:model.live="reversalType" class="mt-1.5 block w-full"><option value="void" @disabled(! $sale->completed_at->isToday())>Void — same business date</option><option value="refund">Full refund</option></select><x-input-error :messages="$errors->get('reversalType')" class="mt-2" /></div>
            <div><x-input-label for="reversal-reason" value="Reason" /><textarea id="reversal-reason" wire:model="reason" rows="3" maxlength="500" class="sniper-input mt-1.5" placeholder="Give a specific reason (minimum 10 characters)"></textarea><x-input-error :messages="$errors->get('reason')" class="mt-2" /></div>
            @if ($reversalType === 'refund')
                <label class="flex items-start gap-3 rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200"><input wire:model="restockInventory" type="checkbox" class="mt-1 rounded border-slate-300 text-sniper-red focus:ring-sniper-red" /><span class="text-sm text-sniper-navy"><span class="font-semibold">Return items to inventory</span><span class="mt-1 block text-xs text-sniper-slate">Clear this for damaged or non-returned goods.</span></span></label>
            @endif
            <div><x-input-label for="authorization-password" value="Administrator Password" /><x-text-input id="authorization-password" wire:model="authorizationPassword" type="password" class="mt-1.5 block w-full" autocomplete="current-password" /><x-input-error :messages="$errors->get('authorizationPassword')" class="mt-2" /></div>
            <label class="flex items-start gap-3"><input wire:model="confirmed" type="checkbox" class="mt-1 rounded border-slate-300 text-sniper-red focus:ring-sniper-red" /><span class="text-xs leading-5 text-sniper-slate">I confirm this is a full {{ $reversalType }} and understand it cannot be edited or deleted.</span></label><x-input-error :messages="$errors->get('confirmed')" class="mt-2" />
            <button type="button" wire:click="process" wire:loading.attr="disabled" wire:confirm="Process this irreversible sale reversal?" class="sniper-btn-primary w-full">Authorize {{ $reversalType === 'void' ? 'Void' : 'Refund' }}</button>
        </div>
    @endif
</div>
