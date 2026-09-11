<div class="sniper-card p-5">
    <div class="sniper-kicker">Partial item refund</div>
    <p class="mt-2 text-xs leading-5 text-sniper-slate">Return selected quantities without changing the original Sales Invoice. Each refund is permanent and independently auditable.</p>

    @if (session('partial-refund-success'))
        <div class="sniper-alert-success mt-4">{{ session('partial-refund-success') }}</div>
    @endif

    @if ($sale->refunds->isNotEmpty())
        <div class="mt-4 space-y-2">
            @foreach ($sale->refunds->sortByDesc('processed_at') as $refund)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950">
                    <div class="flex items-center justify-between gap-3"><span class="font-bold">{{ $refund->refund_number }}</span><span class="font-bold">₱{{ number_format((float) $refund->refund_amount, 2) }}</span></div>
                    <div class="mt-1 text-xs">{{ $refund->items->sum('quantity') }} unit(s) · {{ $refund->processed_at->format('M d, Y g:i A') }} · {{ $refund->authorizedBy->name }}</div>
                    <div class="mt-1 text-xs">{{ $refund->reason }} · Inventory {{ $refund->inventory_restocked ? 'restocked' : 'not restocked' }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($sale->adjustment)
        <div class="mt-4 rounded-lg bg-slate-100 px-3 py-2 text-sm text-sniper-slate">Partial refunds are unavailable because this sale has a full reversal.</div>
    @else
        @error('partialRefund')<div class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</div>@enderror

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b text-left text-xs uppercase text-sniper-slate"><th class="py-2">Item</th><th class="py-2 text-center">Sold</th><th class="py-2 text-center">Refunded</th><th class="py-2 text-right">Return now</th></tr></thead>
                <tbody>
                    @foreach ($sale->items as $item)
                        @php($refunded = (int) ($refundedQuantities[$item->id] ?? 0))
                        @php($remaining = $item->quantity - $refunded)
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-3"><div class="font-semibold text-sniper-navy">{{ $item->product_name }}</div><div class="text-xs text-sniper-slate">₱{{ number_format((float) $item->unit_price, 2) }} each</div></td>
                            <td class="py-3 text-center">{{ $item->quantity }}</td>
                            <td class="py-3 text-center">{{ $refunded }}</td>
                            <td class="py-3 text-right"><input wire:model="quantities.{{ $item->id }}" type="number" min="0" max="{{ $remaining }}" class="sniper-input ml-auto w-20 text-right" @disabled($remaining === 0) /></td>
                        </tr>
                        @error('quantities.'.$item->id)<tr><td colspan="4" class="pb-2 text-xs text-red-600">{{ $message }}</td></tr>@enderror
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 space-y-4">
            <div><x-input-label for="partial-refund-reason" value="Reason" /><textarea id="partial-refund-reason" wire:model="reason" rows="3" maxlength="500" class="sniper-input mt-1.5" placeholder="Give a specific reason (minimum 10 characters)"></textarea><x-input-error :messages="$errors->get('reason')" class="mt-2" /></div>
            <label class="flex items-start gap-3 rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200"><input wire:model="restockInventory" type="checkbox" class="mt-1 rounded border-slate-300 text-sniper-red focus:ring-sniper-red" /><span class="text-sm text-sniper-navy"><span class="font-semibold">Return selected items to inventory</span><span class="mt-1 block text-xs text-sniper-slate">Clear this for damaged or non-returned goods.</span></span></label>
            <div><x-input-label for="partial-refund-password" value="Administrator Password" /><x-text-input id="partial-refund-password" wire:model="authorizationPassword" type="password" class="mt-1.5 block w-full" autocomplete="current-password" /><x-input-error :messages="$errors->get('authorizationPassword')" class="mt-2" /></div>
            <label class="flex items-start gap-3"><input wire:model="confirmed" type="checkbox" class="mt-1 rounded border-slate-300 text-sniper-red focus:ring-sniper-red" /><span class="text-xs leading-5 text-sniper-slate">I confirm the item quantities and understand this refund cannot be edited or deleted.</span></label><x-input-error :messages="$errors->get('confirmed')" class="mt-2" />
            <button type="button" wire:click="process" wire:loading.attr="disabled" wire:confirm="Process this irreversible partial refund?" class="sniper-btn-primary w-full">Authorize Partial Refund</button>
        </div>
    @endif
</div>
