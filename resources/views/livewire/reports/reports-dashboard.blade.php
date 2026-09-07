<div class="sniper-page">
    <div class="sniper-page-header">
        <div>
            <div class="sniper-kicker">Business Intelligence</div>
            <h1 class="sniper-title mt-1">Reports</h1>
            <p class="sniper-subtitle">Track sales performance, inventory position, top products, and low-stock risk.</p>
        </div>
        <span class="sniper-badge-navy">Insights that matter</span>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="sniper-card p-5 sm:p-6">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div><div class="sniper-stat-label">Daily Sales</div><div class="mt-1 font-heading text-3xl font-bold text-sniper-navy">₱{{ number_format($dailySales, 2) }}</div><div class="mt-2 text-sm text-sniper-slate">{{ number_format($dailyTransactions) }} transaction(s)</div></div>
                <div class="w-full sm:w-auto"><x-input-label for="report-date" value="Report Date" /><x-text-input id="report-date" wire:model.live="reportDate" type="date" class="mt-1.5 block w-full" /></div>
            </div>
        </div>
        <div class="sniper-card p-5 sm:p-6">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div><div class="sniper-stat-label">Monthly Sales</div><div class="mt-1 font-heading text-3xl font-bold text-sniper-navy">₱{{ number_format($monthlySales, 2) }}</div><div class="mt-2 text-sm text-sniper-slate">{{ number_format($monthlyTransactions) }} transaction(s)</div></div>
                <div class="w-full sm:w-auto"><x-input-label for="report-month" value="Report Month" /><x-text-input id="report-month" wire:model.live="reportMonth" type="month" class="mt-1.5 block w-full" /></div>
            </div>
        </div>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-3">
        <div class="sniper-stat"><div class="sniper-stat-label">Products</div><div class="sniper-stat-value">{{ number_format($productCount) }}</div></div>
        <div class="sniper-stat"><div class="sniper-stat-label">Units on Hand</div><div class="sniper-stat-value">{{ number_format($unitsOnHand) }}</div></div>
        <div class="sniper-stat"><div class="sniper-stat-label">Inventory Cost</div><div class="sniper-stat-value">₱{{ number_format($inventoryCost, 2) }}</div></div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="sniper-section">
            <div class="sniper-section-header"><div class="flex items-center justify-between gap-4"><div><h2 class="font-heading text-lg font-bold text-sniper-navy">Top Products</h2><p class="mt-1 text-sm text-sniper-slate">Top 10 products by quantity sold for the selected month.</p></div><span class="sniper-badge-success">Performance</span></div></div>
            <div class="overflow-x-auto"><table class="sniper-table"><thead><tr><th>Product</th><th class="!text-right">Qty</th><th class="!text-right">Sales</th></tr></thead><tbody>
                @forelse ($topProducts as $product)
                    <tr><td><div class="font-semibold text-sniper-navy">{{ $product->product_name }}</div><div class="mt-0.5 text-xs text-sniper-slate">{{ $product->sku }}</div></td><td class="!text-right whitespace-nowrap">{{ number_format($product->quantity_sold) }}</td><td class="!text-right whitespace-nowrap font-bold !text-sniper-navy">₱{{ number_format((float) $product->sales_total, 2) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="sniper-empty">No sales for the selected month.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>

        <section class="sniper-section">
            <div class="sniper-section-header"><div class="flex items-center justify-between gap-4"><div><h2 class="font-heading text-lg font-bold text-sniper-navy">Low Stock</h2><p class="mt-1 text-sm text-sniper-slate">Products at or below their configured low-stock level.</p></div><span class="sniper-badge-danger">Attention</span></div></div>
            <div class="overflow-x-auto"><table class="sniper-table"><thead><tr><th>Product</th><th class="!text-right">Stock</th><th class="!text-right">Threshold</th></tr></thead><tbody>
                @forelse ($lowStockProducts as $product)
                    <tr><td><div class="font-semibold text-sniper-navy">{{ $product->name }}</div><div class="mt-0.5 text-xs text-sniper-slate">{{ $product->sku }}</div></td><td class="!text-right whitespace-nowrap font-bold text-sniper-red">{{ number_format($product->stock_quantity) }}</td><td class="!text-right whitespace-nowrap">{{ number_format($product->low_stock_level) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="sniper-empty"><span class="sniper-badge-success">All clear</span><div class="mt-3">No low-stock products.</div></td></tr>
                @endforelse
            </tbody></table></div>
        </section>
    </div>
</div>
