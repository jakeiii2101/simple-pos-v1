<div class="sniper-page">
    <div class="sniper-page-header">
        <div>
            <div class="sniper-kicker">Stock Control</div>
            <h1 class="sniper-title mt-1">Inventory</h1>
            <p class="sniper-subtitle">Record stock in, stock out, and controlled adjustments with a clear audit trail.</p>
        </div>
        <button type="button" wire:click="createMovement" class="sniper-btn-primary">+ Stock Movement</button>
    </div>

    @if (session('success')) <div class="sniper-alert-success mb-5">{{ session('success') }}</div> @endif

    @if ($showForm)
        <form wire:submit="save" class="sniper-form-panel mb-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div><h2 class="font-heading text-lg font-bold text-sniper-navy">Record Stock Movement</h2><p class="mt-1 text-sm text-sniper-slate">Every change is logged with product, user, reason, and before/after stock.</p></div>
                <span class="sniper-badge-navy">Inventory control</span>
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div class="xl:col-span-2"><x-input-label for="inventory-product" value="Product" /><select id="inventory-product" wire:model="productId" class="mt-1.5 block w-full"><option value="">Select product</option>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }} — {{ $product->sku }} (Stock: {{ $product->stock_quantity }})</option>@endforeach</select><x-input-error :messages="$errors->get('productId')" class="mt-2" /></div>
                <div><x-input-label for="inventory-type" value="Movement Type" /><select id="inventory-type" wire:model="type" class="mt-1.5 block w-full"><option value="stock_in">Stock In</option><option value="stock_out">Stock Out</option><option value="adjustment">Adjustment</option></select><x-input-error :messages="$errors->get('type')" class="mt-2" /></div>
                <div><x-input-label for="inventory-quantity" value="Quantity" /><x-text-input id="inventory-quantity" wire:model="quantity" type="number" class="mt-1.5 block w-full" /><p class="mt-1.5 text-xs text-sniper-slate">For adjustments, use a negative value to decrease stock.</p><x-input-error :messages="$errors->get('quantity')" class="mt-2" /></div>
                <div><x-input-label for="inventory-reference" value="Reference" /><x-text-input id="inventory-reference" wire:model="reference" type="text" class="mt-1.5 block w-full" maxlength="100" placeholder="DR / PO / Memo (optional)" /><x-input-error :messages="$errors->get('reference')" class="mt-2" /></div>
                <div><x-input-label for="inventory-reason" value="Reason" /><x-text-input id="inventory-reason" wire:model="reason" type="text" class="mt-1.5 block w-full" maxlength="255" placeholder="Required reason" /><x-input-error :messages="$errors->get('reason')" class="mt-2" /></div>
            </div>
            <div class="mt-6 flex flex-wrap justify-end gap-3"><x-secondary-button type="button" wire:click="cancel">Cancel</x-secondary-button><x-primary-button type="submit">Save Movement</x-primary-button></div>
        </form>
    @endif

    <div class="sniper-table-wrap">
        <div class="sniper-section-header"><h2 class="font-heading text-base font-bold text-sniper-navy">Stock Movement History</h2><p class="mt-1 text-xs text-sniper-slate">Use this audit trail to understand how and why inventory changed.</p></div>
        <table class="sniper-table">
            <thead><tr><th>Date</th><th>Product</th><th>Type</th><th class="!text-right">Qty</th><th class="!text-right">Before</th><th class="!text-right">After</th><th>Reference / Reason</th><th>User</th></tr></thead>
            <tbody>
                @forelse ($movements as $movement)
                    <tr wire:key="movement-{{ $movement->id }}">
                        <td class="whitespace-nowrap">{{ $movement->created_at->format('M d, Y H:i') }}</td>
                        <td><div class="font-semibold text-sniper-navy">{{ $movement->product->name }}</div><div class="mt-0.5 text-xs text-sniper-slate">{{ $movement->product->sku }}</div></td>
                        <td>@if($movement->type === 'stock_in')<span class="sniper-badge-success">Stock In</span>@elseif($movement->type === 'stock_out')<span class="sniper-badge-danger">Stock Out</span>@else<span class="sniper-badge-warning">Adjustment</span>@endif</td>
                        <td class="!text-right whitespace-nowrap font-bold {{ $movement->quantity >= 0 ? 'text-emerald-700' : 'text-sniper-red' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td>
                        <td class="!text-right whitespace-nowrap">{{ $movement->stock_before }}</td>
                        <td class="!text-right whitespace-nowrap font-semibold !text-sniper-navy">{{ $movement->stock_after }}</td>
                        <td>@if ($movement->reference)<div class="font-semibold text-sniper-navy">{{ $movement->reference }}</div>@endif<div class="mt-0.5 text-sniper-slate">{{ $movement->reason }}</div></td>
                        <td class="whitespace-nowrap">{{ $movement->user->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="sniper-empty">No stock movements yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
