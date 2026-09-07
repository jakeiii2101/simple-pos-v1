<div class="sniper-page">
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="sniper-kicker">Business overview</div>
            <h1 class="sniper-title mt-2">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
            <p class="sniper-copy mt-2">Today’s live POS performance, inventory alerts, and recent activity in one view.</p>
        </div>
        <a href="{{ route('pos', [], false) }}" wire:navigate class="sniper-btn-primary gap-2 self-start sm:self-auto">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l2.4 10.2a2 2 0 0 0 2 1.5h7.8a2 2 0 0 0 1.9-1.4L21 7H6m4 12h.01M18 19h.01"/></svg>
            New Sale
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="sniper-stat">
            <div class="flex items-center justify-between gap-3"><div class="sniper-stat-label">Net Sales Today</div><span class="sniper-badge-success">Live</span></div>
            <div class="sniper-stat-value">₱{{ number_format($salesToday, 2) }}</div>
            <div class="mt-2 text-xs text-sniper-slate">Gross ₱{{ number_format($grossSalesToday, 2) }} · Discounts ₱{{ number_format($discountsToday, 2) }}</div>
        </div>
        <div class="sniper-stat">
            <div class="flex items-center justify-between gap-3"><div class="sniper-stat-label">Transactions Today</div><span class="sniper-badge-neutral">Completed</span></div>
            <div class="sniper-stat-value">{{ number_format($transactionsToday) }}</div>
            <div class="mt-2 text-xs text-sniper-slate">Completed POS transactions since midnight.</div>
        </div>
        <div class="sniper-stat">
            <div class="flex items-center justify-between gap-3"><div class="sniper-stat-label">Items Sold Today</div><span class="sniper-badge-neutral">Units</span></div>
            <div class="sniper-stat-value">{{ number_format($itemsSoldToday) }}</div>
            <div class="mt-2 text-xs text-sniper-slate">Total product units across today’s completed sales.</div>
        </div>
        <div class="sniper-stat">
            <div class="flex items-center justify-between gap-3"><div class="sniper-stat-label">Low Stock</div><span class="{{ $lowStockCount > 0 ? 'sniper-badge-danger' : 'sniper-badge-success' }}">{{ $lowStockCount > 0 ? 'Attention' : 'All clear' }}</span></div>
            <div class="sniper-stat-value">{{ number_format($lowStockCount) }}</div>
            <div class="mt-2 text-xs text-sniper-slate">Products at or below their configured threshold.</div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_.65fr]">
        <section class="sniper-section">
            <div class="sniper-section-header">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div><h2 class="font-heading text-lg font-bold text-sniper-navy">Recent Sales</h2><p class="mt-1 text-sm text-sniper-slate">Latest completed transactions across SniperPOS.</p></div>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('sales', [], false) }}" wire:navigate class="sniper-action-link">View Sales History</a>
                    @endif
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="sniper-table">
                    <thead><tr><th>Receipt</th><th>Cashier</th><th>Payment</th><th class="!text-right">Total</th><th class="!text-right">Action</th></tr></thead>
                    <tbody>
                        @forelse($recentSales as $sale)
                            @php
                                $method = $sale->payment?->method ?? 'cash';
                                $methodLabel = match ($method) { 'gcash' => 'GCash', 'card' => 'Card', 'other' => 'Other', default => 'Cash' };
                            @endphp
                            <tr>
                                <td><div class="font-semibold text-sniper-navy">{{ $sale->sale_number }}</div><div class="mt-0.5 text-xs text-sniper-slate">{{ $sale->completed_at->format('M d, g:i A') }}</div></td>
                                <td>{{ $sale->user->name }}</td>
                                <td><span class="sniper-badge-neutral">{{ $methodLabel }}</span></td>
                                <td class="!text-right whitespace-nowrap font-bold !text-sniper-navy">₱{{ number_format((float) $sale->total, 2) }}</td>
                                <td class="!text-right whitespace-nowrap">
                                    @if(auth()->user()->isAdmin())
                                        <a href="{{ route('sales.show', ['sale' => $sale->id], false) }}" wire:navigate class="sniper-action-link">Details</a>
                                    @else
                                        <a href="{{ route('sales.receipt', ['sale' => $sale->id], false) }}" target="_blank" class="sniper-action-link">Receipt</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="sniper-empty">No completed sales yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="space-y-6">
            <section class="sniper-card overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-5">
                    <div class="sniper-kicker">Today</div>
                    <h2 class="mt-1 font-heading text-lg font-bold text-sniper-navy">Top Products</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($topProductsToday as $index => $product)
                        <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                            <div class="flex min-w-0 items-center gap-3"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-sniper-navy">{{ $index + 1 }}</span><div class="min-w-0"><div class="truncate text-sm font-semibold text-sniper-navy">{{ $product->product_name }}</div><div class="truncate text-xs text-sniper-slate">{{ $product->sku }}</div></div></div>
                            <div class="shrink-0 text-sm font-bold text-sniper-navy">{{ number_format($product->quantity_sold) }}</div>
                        </div>
                    @empty
                        <div class="sniper-empty">No products sold today.</div>
                    @endforelse
                </div>
            </section>

            @if(auth()->user()->isAdmin())
                <section class="sniper-card p-5">
                    <div class="sniper-kicker">Admin shortcuts</div>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        @foreach ([['inventory', 'Inventory'], ['sales', 'Sales'], ['reports', 'Reports'], ['products', 'Products']] as [$routeName, $label])
                            <a href="{{ route($routeName, [], false) }}" wire:navigate class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-center text-sm font-semibold text-sniper-navy hover:border-red-200 hover:text-sniper-red">{{ $label }}</a>
                        @endforeach
                    </div>
                </section>
            @else
                <section class="sniper-card overflow-hidden bg-sniper-navy p-5 text-white ring-0">
                    <x-sniper-icon class="h-10 w-10 text-white" />
                    <div class="mt-4 text-[10px] font-bold uppercase tracking-[0.2em] text-red-300">Cashier Workspace</div>
                    <h2 class="mt-2 font-heading text-xl font-bold text-white">Precision in Every Sale.</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Use the POS for accurate checkout and immediate receipt access.</p>
                </section>
            @endif
        </div>
    </div>
</div>
