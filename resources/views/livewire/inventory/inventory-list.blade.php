<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold">Inventory</h1>
                        <p class="mt-1 text-sm text-gray-500">Record stock in, stock out, and manual adjustments.</p>
                    </div>

                    <button
                        type="button"
                        wire:click="createMovement"
                        class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        + Stock Movement
                    </button>
                </div>

                @if (session('success'))
                    <div class="mt-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($showForm)
                    <form wire:submit="save" class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-5">
                        <h2 class="text-lg font-medium text-gray-900">Record Stock Movement</h2>

                        <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div class="lg:col-span-2">
                                <x-input-label for="inventory-product" value="Product" />
                                <select id="inventory-product" wire:model="productId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select product</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} — {{ $product->sku }} (Stock: {{ $product->stock_quantity }})</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('productId')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="inventory-type" value="Movement Type" />
                                <select id="inventory-type" wire:model="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="stock_in">Stock In</option>
                                    <option value="stock_out">Stock Out</option>
                                    <option value="adjustment">Adjustment</option>
                                </select>
                                <x-input-error :messages="$errors->get('type')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="inventory-quantity" value="Quantity" />
                                <x-text-input id="inventory-quantity" wire:model="quantity" type="number" class="mt-1 block w-full" />
                                <p class="mt-1 text-xs text-gray-500">For adjustments, use a negative value to decrease stock.</p>
                                <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="inventory-reference" value="Reference" />
                                <x-text-input id="inventory-reference" wire:model="reference" type="text" class="mt-1 block w-full" maxlength="100" placeholder="DR / PO / Memo (optional)" />
                                <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="inventory-reason" value="Reason" />
                                <x-text-input id="inventory-reason" wire:model="reason" type="text" class="mt-1 block w-full" maxlength="255" placeholder="Required reason" />
                                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end gap-3">
                            <x-secondary-button type="button" wire:click="cancel">Cancel</x-secondary-button>
                            <x-primary-button type="submit">Save Movement</x-primary-button>
                        </div>
                    </form>
                @endif

                <div class="mt-6 overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Qty</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Before</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">After</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reference / Reason</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($movements as $movement)
                                <tr wire:key="movement-{{ $movement->id }}">
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $movement->created_at->format('M d, Y H:i') }}</td>
                                    <td class="px-4 py-4 text-sm">
                                        <div class="font-medium text-gray-900">{{ $movement->product->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $movement->product->sku }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $movement->type === 'stock_in' ? 'bg-green-100 text-green-800' : ($movement->type === 'stock_out' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                            {{ ucwords(str_replace('_', ' ', $movement->type)) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-semibold {{ $movement->quantity >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm text-gray-600">{{ $movement->stock_before }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium text-gray-900">{{ $movement->stock_after }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-600">
                                        @if ($movement->reference)
                                            <div class="font-medium text-gray-800">{{ $movement->reference }}</div>
                                        @endif
                                        <div>{{ $movement->reason }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $movement->user->name }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500">No stock movements yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
