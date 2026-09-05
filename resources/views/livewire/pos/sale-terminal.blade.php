<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">POS</h1>
                <p class="mt-1 text-sm text-gray-500">Scan or search products, build the cart, and complete a cash sale.</p>
            </div>
            <div class="text-sm text-gray-500">Cashier: <span class="font-medium text-gray-800">{{ auth()->user()->name }}</span></div>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
                @if ($lastSaleId)
                    <a href="{{ route('sales.receipt', ['sale' => $lastSaleId], false) }}" target="_blank" class="ml-2 font-semibold underline">Print receipt</a>
                @endif
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-5">
            <section class="lg:col-span-3">
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <form wire:submit="addBySearch" class="flex gap-2">
                        <div class="flex-1">
                            <x-input-label for="pos-search" value="Product / SKU / Barcode" />
                            <x-text-input id="pos-search" wire:model.live.debounce.250ms="search" type="text" class="mt-1 block w-full" placeholder="Scan barcode or type product name" autofocus autocomplete="off" />
                            <x-input-error :messages="$errors->get('search')" class="mt-2" />
                        </div>
                        <div class="flex items-end">
                            <x-primary-button type="submit">Add</x-primary-button>
                        </div>
                    </form>

                    <div class="mt-5 overflow-hidden rounded-lg border border-gray-200">
                        <div class="bg-gray-50 px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Available products</div>
                        <div class="divide-y divide-gray-200">
                            @forelse ($products as $product)
                                <button type="button" wire:click="addProduct({{ $product->id }})" class="flex w-full items-center justify-between gap-4 px-4 py-3 text-left hover:bg-gray-50">
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $product->name }}</div>
                                        <div class="mt-0.5 text-xs text-gray-500">SKU: {{ $product->sku }} @if($product->barcode) · {{ $product->barcode }} @endif</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-gray-900">₱{{ number_format((float) $product->selling_price, 2) }}</div>
                                        <div class="text-xs {{ $product->isLowStock() ? 'text-red-600' : 'text-gray-500' }}">Stock: {{ $product->stock_quantity }}</div>
                                    </div>
                                </button>
                            @empty
                                <div class="px-4 py-8 text-center text-sm text-gray-500">No available products found.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <section class="lg:col-span-2">
                <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">Cart</h2>
                        @if ($cart)
                            <button type="button" wire:click="clearCart" wire:confirm="Clear the current cart?" class="text-sm font-medium text-red-600 hover:text-red-800">Clear</button>
                        @endif
                    </div>

                    @error('cart')
                        <div class="mx-5 mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</div>
                    @enderror

                    <div class="divide-y divide-gray-200">
                        @forelse ($cart as $item)
                            <div wire:key="cart-{{ $item['id'] }}" class="px-5 py-4">
                                <div class="flex justify-between gap-4">
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $item['name'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $item['sku'] }} · ₱{{ number_format($item['price'], 2) }} each</div>
                                    </div>
                                    <div class="font-semibold text-gray-900">₱{{ number_format($item['price'] * $item['quantity'], 2) }}</div>
                                </div>
                                <div class="mt-3 flex items-center justify-between">
                                    <div class="inline-flex items-center rounded-md border border-gray-300">
                                        <button type="button" wire:click="decrease({{ $item['id'] }})" class="px-3 py-1.5 text-gray-700 hover:bg-gray-50">−</button>
                                        <span class="min-w-10 border-x border-gray-300 px-3 py-1.5 text-center text-sm font-medium">{{ $item['quantity'] }}</span>
                                        <button type="button" wire:click="increase({{ $item['id'] }})" class="px-3 py-1.5 text-gray-700 hover:bg-gray-50">+</button>
                                    </div>
                                    <button type="button" wire:click="remove({{ $item['id'] }})" class="text-sm text-red-600 hover:text-red-800">Remove</button>
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-10 text-center text-sm text-gray-500">Cart is empty.</div>
                        @endforelse
                    </div>

                    <div class="border-t border-gray-200 bg-gray-50 px-5 py-5">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>₱{{ number_format($subtotal, 2) }}</span></div>
                            <div class="flex justify-between text-lg font-bold text-gray-900"><span>Total</span><span>₱{{ number_format($total, 2) }}</span></div>
                        </div>

                        <div class="mt-5">
                            <x-input-label for="cash-received" value="Cash Received" />
                            <x-text-input id="cash-received" wire:model.live.debounce.200ms="cashReceived" type="number" step="0.01" min="0" class="mt-1 block w-full text-right text-lg" placeholder="0.00" />
                            <x-input-error :messages="$errors->get('cashReceived')" class="mt-2" />
                        </div>

                        <div class="mt-3 flex justify-between rounded-md bg-white px-3 py-3 text-sm ring-1 ring-gray-200">
                            <span class="text-gray-600">Change</span>
                            <span class="font-bold text-gray-900">₱{{ number_format($changeDue, 2) }}</span>
                        </div>

                        <button type="button" wire:click="completeSale" wire:loading.attr="disabled" class="mt-5 inline-flex w-full items-center justify-center rounded-md bg-gray-900 px-4 py-3 text-sm font-semibold uppercase tracking-wider text-white hover:bg-gray-800 disabled:opacity-50">
                            Complete Sale
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
