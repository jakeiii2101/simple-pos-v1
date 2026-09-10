<div class="sniper-page">
    <div class="sniper-page-header">
        <div>
            <div class="sniper-kicker">Catalog</div>
            <h1 class="sniper-title mt-1">Products</h1>
            <p class="sniper-subtitle">Manage pricing, stock thresholds, barcodes, and selling status from one place.</p>
        </div>
        <button type="button" wire:click="create" class="sniper-btn-primary">+ Add Product</button>
    </div>

    @if (session('success')) <div class="sniper-alert-success mb-5">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="sniper-alert-danger mb-5">{{ session('error') }}</div> @endif

    @if ($showForm)
        <form wire:submit="save" class="sniper-form-panel mb-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="font-heading text-lg font-bold text-sniper-navy">{{ $editingId ? 'Edit Product' : 'Add Product' }}</h2>
                    <p class="mt-1 text-sm text-sniper-slate">Enter the essential sales and inventory details. Stock changes after creation belong in Inventory.</p>
                </div>
                <span class="sniper-badge-navy">Product setup</span>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div><x-input-label for="product-name" value="Product Name" /><x-text-input id="product-name" wire:model="name" type="text" class="mt-1.5 block w-full" maxlength="150" /><x-input-error :messages="$errors->get('name')" class="mt-2" /></div>
                <div><x-input-label for="product-category" value="Category" /><select id="product-category" wire:model="categoryId" class="mt-1.5 block w-full"><option value="">Select a category</option>@foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('categoryId')" class="mt-2" /></div>
                <div><x-input-label for="product-status" value="Status" /><select id="product-status" wire:model="status" class="mt-1.5 block w-full"><option value="active">Active</option><option value="inactive">Inactive</option></select><x-input-error :messages="$errors->get('status')" class="mt-2" /></div>
                <div><x-input-label for="product-sku" value="SKU" /><x-text-input id="product-sku" wire:model="sku" type="text" class="mt-1.5 block w-full" maxlength="100" autocomplete="off" /><x-input-error :messages="$errors->get('sku')" class="mt-2" /></div>
                <div><x-input-label for="product-barcode" value="Barcode" /><x-text-input id="product-barcode" wire:model="barcode" type="text" class="mt-1.5 block w-full" maxlength="100" autocomplete="off" placeholder="Optional" /><x-input-error :messages="$errors->get('barcode')" class="mt-2" /></div>
                <div><x-input-label for="cost-price" value="Cost Price" /><x-text-input id="cost-price" wire:model="costPrice" type="number" step="0.01" min="0" class="mt-1.5 block w-full" /><x-input-error :messages="$errors->get('costPrice')" class="mt-2" /></div>
                <div><x-input-label for="selling-price" value="Selling Price" /><x-text-input id="selling-price" wire:model="sellingPrice" type="number" step="0.01" min="0" class="mt-1.5 block w-full" /><x-input-error :messages="$errors->get('sellingPrice')" class="mt-2" /></div>
                <div><x-input-label for="tax-type" value="Tax Classification" /><select id="tax-type" wire:model.live="taxType" class="mt-1.5 block w-full"><option value="vatable">VATable</option><option value="vat_exempt">VAT-exempt</option><option value="zero_rated">Zero-rated</option></select><x-input-error :messages="$errors->get('taxType')" class="mt-2" /></div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><label class="flex items-start gap-3"><input wire:model="isSeniorPwdDiscountEligible" type="checkbox" class="mt-1 rounded border-slate-300 text-sniper-red focus:ring-sniper-red" @disabled($taxType !== 'vatable') /><span><span class="block text-sm font-semibold text-sniper-navy">Senior/PWD discount eligible</span><span class="mt-1 block text-xs text-sniper-slate">Enable only for qualifying VATable goods or services.</span></span></label><x-input-error :messages="$errors->get('isSeniorPwdDiscountEligible')" class="mt-2" /></div>
                <div>
                    <x-input-label for="stock-quantity" value="Stock Quantity" />
                    @if ($editingId)
                        <x-text-input id="stock-quantity" :value="$stockQuantity" type="number" class="mt-1.5 block w-full" disabled />
                        <p class="mt-1.5 text-xs text-sniper-slate">Use Inventory to change existing stock.</p>
                    @else
                        <x-text-input id="stock-quantity" wire:model="stockQuantity" type="number" min="0" class="mt-1.5 block w-full" /><x-input-error :messages="$errors->get('stockQuantity')" class="mt-2" />
                    @endif
                </div>
                <div><x-input-label for="low-stock-level" value="Low Stock Level" /><x-text-input id="low-stock-level" wire:model="lowStockLevel" type="number" min="0" class="mt-1.5 block w-full" /><x-input-error :messages="$errors->get('lowStockLevel')" class="mt-2" /></div>
            </div>
            <div class="mt-6 flex flex-wrap justify-end gap-3"><x-secondary-button type="button" wire:click="cancel">Cancel</x-secondary-button><x-primary-button type="submit">{{ $editingId ? 'Update Product' : 'Save Product' }}</x-primary-button></div>
        </form>
    @endif

    <div class="sniper-table-wrap">
        <div class="sniper-section-header">
            <h2 class="font-heading text-base font-bold text-sniper-navy">Product Catalog</h2>
            <p class="mt-1 text-xs text-sniper-slate">Low-stock products are highlighted for quick action.</p>
        </div>
        <table class="sniper-table">
            <thead><tr><th>Product</th><th>SKU / Barcode</th><th>Category</th><th>Tax</th><th class="!text-right">Cost</th><th class="!text-right">Price</th><th class="!text-right">Stock</th><th>Status</th><th class="!text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($products as $product)
                    <tr wire:key="product-{{ $product->id }}">
                        <td class="font-semibold !text-sniper-navy">{{ $product->name }}</td>
                        <td><div>{{ $product->sku }}</div><div class="mt-0.5 text-xs text-slate-400">{{ $product->barcode ?: 'No barcode' }}</div></td>
                        <td>{{ $product->category->name }}</td>
                        <td><span class="sniper-badge-neutral">{{ str($product->tax_type)->replace('_', ' ')->title() }}</span>@if($product->is_senior_pwd_discount_eligible)<div class="mt-1 text-[10px] font-semibold text-emerald-700">Senior/PWD eligible</div>@endif</td>
                        <td class="!text-right whitespace-nowrap">₱{{ number_format((float) $product->cost_price, 2) }}</td>
                        <td class="!text-right whitespace-nowrap font-semibold !text-sniper-navy">₱{{ number_format((float) $product->selling_price, 2) }}</td>
                        <td class="!text-right whitespace-nowrap"><span class="{{ $product->isLowStock() ? 'font-bold text-sniper-red' : 'font-semibold text-sniper-navy' }}">{{ $product->stock_quantity }}</span>@if ($product->isLowStock())<div class="mt-1"><span class="sniper-badge-danger">Low stock</span></div>@endif</td>
                        <td><span class="{{ $product->status === 'active' ? 'sniper-badge-success' : 'sniper-badge-neutral' }}">{{ ucfirst($product->status) }}</span></td>
                        <td class="!text-right whitespace-nowrap"><button type="button" wire:click="edit({{ $product->id }})" class="sniper-action-link">Edit</button><button type="button" wire:click="delete({{ $product->id }})" wire:confirm="Delete this product?" class="sniper-action-danger ml-4">Delete</button></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="sniper-empty">No products yet. Add your first product to get started.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
