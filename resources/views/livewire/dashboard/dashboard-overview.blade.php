<div class="sniper-page">
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="sniper-kicker">Business overview</div>
            <h1 class="sniper-title mt-2">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
            <p class="sniper-copy mt-2">Today’s live POS performance, inventory alerts, and recent activity in one view.</p>
            <div class="mt-3 inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-sniper-slate ring-1 ring-slate-200">
                <svg class="h-4 w-4 text-sniper-navy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                <span>{{ now()->format('l, M d, Y · h:i A') }} · {{ config('app.timezone') }}</span>
            </div>
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
                                        <a href="{{ route('sales.invoice', ['sale' => $sale->id], false) }}" target="_blank" class="sniper-action-link">Sales Invoice</a>
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
                    <div class="sniper-kicker">Quick Actions</div>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <a href="{{ route('inventory', [], false) }}" wire:navigate class="group rounded-xl border border-slate-200 bg-white px-3 py-4 text-center hover:border-red-200">
                            <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-sniper-navy group-hover:bg-red-50 group-hover:text-sniper-red">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M5 7l1 13h12l1-13M9 11v5M15 11v5M9 4h6l1 3H8l1-3Z"/></svg>
                            </span>
                            <span class="mt-2 block text-sm font-semibold text-sniper-navy group-hover:text-sniper-red">Inventory</span>
                        </a>

                        <a href="{{ route('sales', [], false) }}" wire:navigate class="group rounded-xl border border-slate-200 bg-white px-3 py-4 text-center hover:border-red-200">
                            <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-sniper-navy group-hover:bg-red-50 group-hover:text-sniper-red">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/></svg>
                            </span>
                            <span class="mt-2 block text-sm font-semibold text-sniper-navy group-hover:text-sniper-red">Sales</span>
                        </a>

                        <a href="{{ route('reports', [], false) }}" wire:navigate class="group rounded-xl border border-slate-200 bg-white px-3 py-4 text-center hover:border-red-200">
                            <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-sniper-navy group-hover:bg-red-50 group-hover:text-sniper-red">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/></svg>
                            </span>
                            <span class="mt-2 block text-sm font-semibold text-sniper-navy group-hover:text-sniper-red">Reports</span>
                        </a>

                        <a href="{{ route('products', [], false) }}" wire:navigate class="group rounded-xl border border-slate-200 bg-white px-3 py-4 text-center hover:border-red-200">
                            <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-sniper-navy group-hover:bg-red-50 group-hover:text-sniper-red">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3 8 4-8 4-8-4 8-4Zm-8 4v10l8 4 8-4V7m-8 4v10"/></svg>
                            </span>
                            <span class="mt-2 block text-sm font-semibold text-sniper-navy group-hover:text-sniper-red">Products</span>
                        </a>
                    </div>
                </section>
            @else
                <section class="sniper-card overflow-hidden bg-sniper-navy p-5 text-white ring-0">
                    <x-sniper-icon class="h-10 w-10 text-white" />
                    <div class="mt-4 text-[10px] font-bold uppercase tracking-[0.2em] text-red-300">Cashier Workspace</div>
                    <h2 class="mt-2 font-heading text-xl font-bold text-white">Precision in Every Sale.</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Use the POS for accurate checkout and immediate Sales Invoice access.</p>
                </section>
            @endif
        </div>
    </div>
</div>
