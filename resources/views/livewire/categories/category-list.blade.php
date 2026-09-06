<div class="sniper-page">
    <div class="sniper-page-header">
        <div>
            <div class="sniper-kicker">Catalog</div>
            <h1 class="sniper-title mt-1">Categories</h1>
            <p class="sniper-subtitle">Organize products into clear groups for faster selling and reporting.</p>
        </div>
        <button type="button" wire:click="create" class="sniper-btn-primary">+ Add Category</button>
    </div>

    @if (session('success'))
        <div class="sniper-alert-success mb-5">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="sniper-alert-danger mb-5">{{ session('error') }}</div>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="sniper-form-panel mb-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-heading text-lg font-bold text-sniper-navy">{{ $editingId ? 'Edit Category' : 'Add Category' }}</h2>
                    <p class="mt-1 text-sm text-sniper-slate">Keep names short and recognizable at checkout.</p>
                </div>
                <span class="sniper-badge-navy">Category setup</span>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div>
                    <x-input-label for="category-name" value="Category Name" />
                    <x-text-input id="category-name" wire:model="name" type="text" class="mt-1.5 block w-full" maxlength="100" autocomplete="off" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="category-status" value="Status" />
                    <select id="category-status" wire:model="status" class="mt-1.5 block w-full">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="category-description" value="Description" />
                    <textarea id="category-description" wire:model="description" rows="3" maxlength="255" class="mt-1.5 block w-full" placeholder="Optional description"></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <x-secondary-button type="button" wire:click="cancel">Cancel</x-secondary-button>
                <x-primary-button type="submit">{{ $editingId ? 'Update Category' : 'Save Category' }}</x-primary-button>
            </div>
        </form>
    @endif

    <div class="sniper-table-wrap">
        <div class="sniper-section-header flex items-center justify-between gap-4">
            <div>
                <h2 class="font-heading text-base font-bold text-sniper-navy">Category Directory</h2>
                <p class="mt-1 text-xs text-sniper-slate">Active categories are available for product assignment.</p>
            </div>
        </div>
        <table class="sniper-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th class="!text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr wire:key="category-{{ $category->id }}">
                        <td class="font-semibold !text-sniper-navy">{{ $category->name }}</td>
                        <td>{{ $category->description ?: '—' }}</td>
                        <td>
                            <span class="{{ $category->status === 'active' ? 'sniper-badge-success' : 'sniper-badge-neutral' }}">{{ ucfirst($category->status) }}</span>
                        </td>
                        <td class="!text-right whitespace-nowrap">
                            <button type="button" wire:click="edit({{ $category->id }})" class="sniper-action-link">Edit</button>
                            <button type="button" wire:click="delete({{ $category->id }})" wire:confirm="Delete this category?" class="sniper-action-danger ml-4">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="sniper-empty">No categories yet. Add your first category to get started.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
