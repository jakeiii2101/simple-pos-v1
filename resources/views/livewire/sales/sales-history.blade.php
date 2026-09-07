<div class="sniper-page">
    <div class="sniper-page-header">
        <div>
            <div class="sniper-kicker">Transactions</div>
            <h1 class="sniper-title mt-1">Sales History</h1>
            <p class="sniper-subtitle">Review completed sales, trace receipts, and filter transaction history quickly.</p>
        </div>
        <a href="{{ route('pos', [], false) }}" wire:navigate class="sniper-btn-primary">Open POS</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="sniper-stat"><div class="sniper-stat-label">Transactions</div><div class="sniper-stat-value">{{ number_format($transactionCount) }}</div><div class="mt-2 text-xs text-sniper-slate">Completed sales in the current result set</div></div>
        <div class="sniper-stat"><div class="sniper-stat-label">Gross Sales</div><div class="sniper-stat-value">₱{{ number_format($grossSales, 2) }}</div><div class="mt-2 text-xs text-sniper-slate">Total sales value before later reporting adjustments</div></div>
        <div class="sniper-stat"><div class="sniper-stat-label">Items Sold</div><div class="sniper-stat-value">{{ number_format($itemsSold) }}</div><div class="mt-2 text-xs text-sniper-slate">Units included in completed transactions</div></div>
    </div>

    <div class="sniper-card mt-6 p-5 sm:p-6">
        <div class="flex items-center justify-between gap-4"><div><h2 class="font-heading text-base font-bold text-sniper-navy">Filter Sales</h2><p class="mt-1 text-xs text-sniper-slate">Search by receipt or cashier, then narrow by date range.</p></div><span class="sniper-badge-navy">History</span></div>
        <div class="mt-5 grid gap-4 md:grid-cols-4">
            <div class="md:col-span-2"><x-input-label for="sales-search" value="Search Sale / Cashier" /><x-text-input id="sales-search" wire:model.live.debounce.300ms="search" type="text" class="mt-1.5 block w-full" placeholder="Receipt number or cashier name" /></div>
            <div><x-input-label for="date-from" value="From" /><x-text-input id="date-from" wire:model.live="dateFrom" type="date" class="mt-1.5 block w-full" /></div>
            <div><x-input-label for="date-to" value="To" /><x-text-input id="date-to" wire:model.live="dateTo" type="date" class="mt-1.5 block w-full" /></div>
        </div>
        <div class="mt-4 flex justify-end"><x-secondary-button type="button" wire:click="clearFilters">Clear Filters</x-secondary-button></div>
    </div>

    <div class="sniper-table-wrap mt-6">
        <div class="sniper-section-header"><h2 class="font-heading text-base font-bold text-sniper-navy">Completed Transactions</h2><p class="mt-1 text-xs text-sniper-slate">Open any receipt in a separate tab for printing or review.</p></div>
        <table class="sniper-table">
            <thead><tr><th>Receipt</th><th>Date</th><th>Cashier</th><th class="!text-right">Items</th><th class="!text-right">Total</th><th class="!text-right">Action</th></tr></thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr wire:key="sale-{{ $sale->id }}">
                        <td class="whitespace-nowrap font-semibold !text-sniper-navy">{{ $sale->sale_number }}</td>
                        <td class="whitespace-nowrap">{{ $sale->completed_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $sale->user->name }}</td>
                        <td class="!text-right whitespace-nowrap">{{ number_format($sale->items->sum('quantity')) }}</td>
                        <td class="!text-right whitespace-nowrap font-bold !text-sniper-navy">₱{{ number_format((float) $sale->total, 2) }}</td>
                        <td class="!text-right whitespace-nowrap"><a href="{{ route('sales.receipt', ['sale' => $sale->id], false) }}" target="_blank" class="sniper-action-link">View Receipt</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="sniper-empty">No completed sales found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
