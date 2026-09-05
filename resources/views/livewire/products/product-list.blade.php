<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold">Products</h1>
                        <p class="mt-1 text-sm text-gray-500">Manage products, pricing, stock, and category assignment.</p>
                    </div>

                    <button
                        type="button"
                        wire:click="create"
                        class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        + Add Product
                    </button>
                </div>

                @if (session('success'))
                    <div class="mt-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($showForm)
                    <form wire:submit="save" class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-5">
                        <h2 class="text-lg font-medium text-gray-900">{{ $editingId ? 'Edit Product' : 'Add Product' }}</h2>

                        <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <x-input-label for="product-name" value="Product Name" />
                                <x-text-input id="product-name" wire:model="name" type="text" class="mt-1 block w-full" maxlength="150" />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="product-category" value="Category" />
                                <select id="product-category" wire:model="categoryId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select a category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('categoryId')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="product-status" value="Status" />
                                <select id="product-status" wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="product-sku" value="SKU" />
                                <x-text-input id="product-sku" wire:model="sku" type="text" class="mt-1 block w-full" maxlength="100" autocomplete="off" />
                                <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="product-barcode" value="Barcode" />
                                <x-text-input id="product-barcode" wire:model="barcode" type="text" class="mt-1 block w-full" maxlength="100" autocomplete="off" placeholder="Optional" />
                                <x-input-error :messages="$errors->get('barcode')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="cost-price" value="Cost Price" />
                                <x-text-input id="cost-price" wire:model="costPrice" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('costPrice')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="selling-price" value="Selling Price" />
                                <x-text-input id="selling-price" wire:model="sellingPrice" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('sellingPrice')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="stock-quantity" value="Stock Quantity" />
                                @if ($editingId)
                                    <x-text-input id="stock-quantity" :value="$stockQuantity" type="number" class="mt-1 block w-full bg-gray-100" disabled />
                                    <p class="mt-1 text-xs text-gray-500">Use Inventory to change stock after product creation.</p>
                                @else
                                    <x-text-input id="stock-quantity" wire:model="stockQuantity" type="number" min="0" class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('stockQuantity')" class="mt-2" />
                                @endif
                            </div>

                            <div>
                                <x-input-label for="low-stock-level" value="Low Stock Level" />
                                <x-text-input id="low-stock-level" wire:model="lowStockLevel" type="number" min="0" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('lowStockLevel')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end gap-3">
                            <x-secondary-button type="button" wire:click="cancel">Cancel</x-secondary-button>
                            <x-primary-button type="submit">{{ $editingId ? 'Update Product' : 'Save Product' }}</x-primary-button>
                        </div>
                    </form>
                @endif

                <div class="mt-6 overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">SKU / Barcode</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Category</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Cost</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Price</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Stock</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($products as $product)
                                <tr wire:key="product-{{ $product->id }}">
                                    <td class="px-4 py-4 text-sm font-medium text-gray-900">{{ $product->name }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-600">
                                        <div>{{ $product->sku }}</div>
                                        <div class="text-xs text-gray-400">{{ $product->barcode ?: 'No barcode' }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-600">{{ $product->category->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm text-gray-600">₱{{ number_format((float) $product->cost_price, 2) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium text-gray-900">₱{{ number_format((float) $product->selling_price, 2) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                        <span class="{{ $product->isLowStock() ? 'font-semibold text-red-600' : 'text-gray-700' }}">{{ $product->stock_quantity }}</span>
                                        @if ($product->isLowStock())
                                            <div class="text-xs text-red-500">Low stock</div>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $product->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                            {{ ucfirst($product->status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium">
                                        <button type="button" wire:click="edit({{ $product->id }})" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                        <button type="button" wire:click="delete({{ $product->id }})" wire:confirm="Delete this product?" class="ml-4 text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500">No products yet. Add your first product to get started.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
