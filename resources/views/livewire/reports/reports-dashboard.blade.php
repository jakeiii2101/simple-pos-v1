<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Reports</h1>
            <p class="mt-1 text-sm text-gray-500">Daily sales, monthly sales, top products, and inventory summary.</p>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Daily Sales</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900">₱{{ number_format($dailySales, 2) }}</div>
                        <div class="mt-1 text-sm text-gray-500">{{ number_format($dailyTransactions) }} transaction(s)</div>
                    </div>
                    <div class="w-full sm:w-auto">
                        <x-input-label for="report-date" value="Report Date" />
                        <x-text-input id="report-date" wire:model.live="reportDate" type="date" class="mt-1 block w-full" />
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Monthly Sales</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900">₱{{ number_format($monthlySales, 2) }}</div>
                        <div class="mt-1 text-sm text-gray-500">{{ number_format($monthlyTransactions) }} transaction(s)</div>
                    </div>
                    <div class="w-full sm:w-auto">
                        <x-input-label for="report-month" value="Report Month" />
                        <x-text-input id="report-month" wire:model.live="reportMonth" type="month" class="mt-1 block w-full" />
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <div class="text-sm text-gray-500">Products</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($productCount) }}</div>
            </div>
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <div class="text-sm text-gray-500">Units on Hand</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($unitsOnHand) }}</div>
            </div>
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <div class="text-sm text-gray-500">Inventory Cost</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">₱{{ number_format($inventoryCost, 2) }}</div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">Top Products</h2>
                    <p class="mt-1 text-sm text-gray-500">Top 10 products by quantity sold for the selected month.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Qty</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Sales</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($topProducts as $product)
                                <tr>
                                    <td class="px-4 py-4 text-sm">
                                        <div class="font-medium text-gray-900">{{ $product->product_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $product->sku }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm text-gray-600">{{ number_format($product->quantity_sold) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium text-gray-900">₱{{ number_format((float) $product->sales_total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-10 text-center text-sm text-gray-500">No sales for the selected month.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">Low Stock</h2>
                    <p class="mt-1 text-sm text-gray-500">Products currently at or below their low-stock level.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Stock</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Threshold</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($lowStockProducts as $product)
                                <tr>
                                    <td class="px-4 py-4 text-sm">
                                        <div class="font-medium text-gray-900">{{ $product->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $product->sku }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-semibold text-red-600">{{ number_format($product->stock_quantity) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm text-gray-600">{{ number_format($product->low_stock_level) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-10 text-center text-sm text-gray-500">No low-stock products.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
