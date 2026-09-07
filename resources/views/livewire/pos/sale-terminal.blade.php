<div class="sniper-page">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="sniper-kicker">Checkout workspace</div>
            <h1 class="sniper-title mt-2">Point of Sale</h1>
            <p class="sniper-copy mt-2">Scan or search products, build the cart, and complete the sale with fewer clicks.</p>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sniper-navy font-heading text-xs font-bold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
            <span>
                <span class="block text-[10px] font-semibold uppercase tracking-[0.12em] text-sniper-slate">Cashier</span>
                <span class="block text-sm font-semibold text-sniper-navy">{{ auth()->user()->name }}</span>
            </span>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-5 flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 sm:flex-row sm:items-center sm:justify-between">
            <span class="font-medium">{{ session('success') }}</span>
            @if ($lastSaleId)
                <a href="{{ route('sales.receipt', ['sale' => $lastSaleId], false) }}" target="_blank" class="font-semibold underline underline-offset-4">Print receipt</a>
            @endif
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
        <section class="sniper-card overflow-hidden">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <form wire:submit="addBySearch" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label for="pos-search" class="sniper-label">Product / SKU / Barcode</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <input id="pos-search" wire:model.live.debounce.250ms="search" type="text" class="sniper-input pl-11" placeholder="Scan barcode or type product name" autofocus autocomplete="off" />
                        </div>
                        <x-input-error :messages="$errors->get('search')" class="mt-2" />
                    </div>
                    <button type="submit" class="sniper-btn-primary h-[42px] gap-2 px-5">
                        <span>Add Product</span>
                        <span aria-hidden="true">＋</span>
                    </button>
                </form>
            </div>

            <div class="flex items-center justify-between bg-slate-50 px-5 py-3 sm:px-6">
                <div>
                    <div class="text-xs font-bold uppercase tracking-[0.14em] text-sniper-navy">Available products</div>
                    <div class="mt-0.5 text-[11px] text-sniper-slate">Choose an item to add it to the cart</div>
                </div>
                <span class="sniper-badge-neutral">Live stock</span>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($products as $product)
                    <button type="button" wire:click="addProduct({{ $product->id }})" class="group flex w-full items-center justify-between gap-4 px-5 py-4 text-left hover:bg-slate-50 sm:px-6">
                        <div class="flex min-w-0 items-center gap-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sniper-navy ring-1 ring-slate-200 group-hover:bg-white">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3 8 4-8 4-8-4 8-4Zm-8 4v10l8 4 8-4V7m-8 4v10"/></svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate font-semibold text-sniper-navy">{{ $product->name }}</span>
                                <span class="mt-1 block truncate text-xs text-sniper-slate">SKU: {{ $product->sku }} @if($product->barcode) · Barcode: {{ $product->barcode }} @endif</span>
                            </span>
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="font-heading text-base font-bold text-sniper-navy">₱{{ number_format((float) $product->selling_price, 2) }}</div>
                            <div class="mt-1 text-xs font-medium {{ $product->isLowStock() ? 'text-red-600' : 'text-emerald-600' }}">{{ $product->isLowStock() ? 'Low stock' : 'In stock' }} · {{ $product->stock_quantity }}</div>
                        </div>
                    </button>
                @empty
                    <div class="px-6 py-14 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></div>
                        <div class="mt-3 text-sm font-semibold text-sniper-navy">No available products found</div>
                        <div class="mt-1 text-xs text-sniper-slate">Try a different product name, SKU, or barcode.</div>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="xl:sticky xl:top-24 xl:self-start">
            <div class="sniper-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <div class="sniper-kicker">Current sale</div>
                        <h2 class="mt-1 font-heading text-lg font-bold text-sniper-navy">Cart</h2>
                    </div>
                    @if ($cart)
                        <button type="button" wire:click="clearCart" wire:confirm="Clear the current cart?" class="rounded-lg px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Clear Cart</button>
                    @endif
                </div>

                @error('cart')
                    <div class="mx-5 mt-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</div>
                @enderror

                <div class="max-h-[420px] divide-y divide-slate-100 overflow-y-auto">
                    @forelse ($cart as $item)
                        <div wire:key="cart-{{ $item['id'] }}" class="px-5 py-4">
                            <div class="flex justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-sniper-navy">{{ $item['name'] }}</div>
                                    <div class="mt-1 text-xs text-sniper-slate">{{ $item['sku'] }} · ₱{{ number_format($item['price'], 2) }} each</div>
                                </div>
                                <div class="shrink-0 font-heading font-bold text-sniper-navy">₱{{ number_format($item['price'] * $item['quantity'], 2) }}</div>
                            </div>
                            <div class="mt-3 flex items-center justify-between">
                                <div class="inline-flex items-center overflow-hidden rounded-lg border border-slate-300 bg-white">
                                    <button type="button" wire:click="decrease({{ $item['id'] }})" class="px-3 py-1.5 text-sniper-navy hover:bg-slate-50">−</button>
                                    <span class="min-w-10 border-x border-slate-300 px-3 py-1.5 text-center text-sm font-semibold text-sniper-navy">{{ $item['quantity'] }}</span>
                                    <button type="button" wire:click="increase({{ $item['id'] }})" class="px-3 py-1.5 text-sniper-navy hover:bg-slate-50">+</button>
                                </div>
                                <button type="button" wire:click="remove({{ $item['id'] }})" class="text-xs font-semibold text-red-600 hover:text-red-700">Remove</button>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-12 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-sniper-navy ring-1 ring-slate-200"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h2l2.4 10.2a2 2 0 0 0 2 1.5h7.8a2 2 0 0 0 1.9-1.4L21 7H6"/></svg></div>
                            <div class="mt-3 text-sm font-semibold text-sniper-navy">Your cart is empty</div>
                            <div class="mt-1 text-xs text-sniper-slate">Add a product to start the sale.</div>
                        </div>
                    @endforelse
                </div>

                <div class="border-t border-slate-200 bg-slate-50 px-5 py-5">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between text-sniper-slate"><span>Subtotal</span><span class="font-medium text-sniper-navy">₱{{ number_format($subtotal, 2) }}</span></div>
                        <div class="flex items-end justify-between border-t border-slate-200 pt-3">
                            <span class="font-heading text-base font-bold text-sniper-navy">Grand Total</span>
                            <span class="font-heading text-2xl font-extrabold tracking-tight text-sniper-navy">₱{{ number_format($total, 2) }}</span>
                        </div>
                    </div>

                    <div class="mt-5">
                        <label for="cash-received" class="sniper-label">Cash Received</label>
                        <input id="cash-received" wire:model.live.debounce.200ms="cashReceived" type="number" step="0.01" min="0" class="sniper-input text-right text-lg font-semibold" placeholder="0.00" />
                        <x-input-error :messages="$errors->get('cashReceived')" class="mt-2" />
                    </div>

                    <div class="mt-3 flex items-center justify-between rounded-xl bg-white px-4 py-3 ring-1 ring-slate-200">
                        <span class="text-sm text-sniper-slate">Change</span>
                        <span class="font-heading text-lg font-bold text-sniper-navy">₱{{ number_format($changeDue, 2) }}</span>
                    </div>

                    <button type="button" wire:click="completeSale" wire:loading.attr="disabled" class="sniper-btn-primary mt-5 w-full gap-2 py-3.5 font-heading uppercase tracking-[0.08em]">
                        <span>Complete Sale</span>
                        <span aria-hidden="true">→</span>
                    </button>
                </div>
            </div>
        </section>
    </div>
</div>
