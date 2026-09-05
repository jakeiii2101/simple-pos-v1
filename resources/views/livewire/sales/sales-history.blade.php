<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Sales History</h1>
            <p class="mt-1 text-sm text-gray-500">Review completed sales and basic sales totals.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <div class="text-sm text-gray-500">Transactions</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($transactionCount) }}</div>
            </div>
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <div class="text-sm text-gray-500">Gross Sales</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">₱{{ number_format($grossSales, 2) }}</div>
            </div>
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <div class="text-sm text-gray-500">Items Sold</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($itemsSold) }}</div>
            </div>
        </div>

        <div class="mt-6 rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <div class="grid gap-4 md:grid-cols-4">
                <div class="md:col-span-2">
                    <x-input-label for="sales-search" value="Search Sale / Cashier" />
                    <x-text-input id="sales-search" wire:model.live.debounce.300ms="search" type="text" class="mt-1 block w-full" placeholder="Receipt number or cashier name" />
                </div>
                <div>
                    <x-input-label for="date-from" value="From" />
                    <x-text-input id="date-from" wire:model.live="dateFrom" type="date" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="date-to" value="To" />
                    <x-text-input id="date-to" wire:model.live="dateTo" type="date" class="mt-1 block w-full" />
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <x-secondary-button type="button" wire:click="clearFilters">Clear Filters</x-secondary-button>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Receipt</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Cashier</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Items</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($sales as $sale)
                        <tr wire:key="sale-{{ $sale->id }}">
                            <td class="whitespace-nowrap px-4 py-4 text-sm font-medium text-gray-900">{{ $sale->sale_number }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $sale->completed_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-4 text-sm text-gray-600">{{ $sale->user->name }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right text-sm text-gray-600">{{ number_format($sale->items->sum('quantity')) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-semibold text-gray-900">₱{{ number_format((float) $sale->total, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                <a href="{{ route('sales.receipt', ['sale' => $sale->id], false) }}" target="_blank" class="font-medium text-indigo-600 hover:text-indigo-900">Receipt</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">No completed sales found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
