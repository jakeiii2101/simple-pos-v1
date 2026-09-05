<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold">Categories</h1>
                        <p class="mt-1 text-sm text-gray-500">Manage the product categories used by Simple POS.</p>
                    </div>

                    <button
                        type="button"
                        wire:click="create"
                        class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        + Add Category
                    </button>
                </div>

                @if (session('success'))
                    <div class="mt-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($showForm)
                    <form wire:submit="save" class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-5">
                        <h2 class="text-lg font-medium text-gray-900">
                            {{ $editingId ? 'Edit Category' : 'Add Category' }}
                        </h2>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="category-name" value="Category Name" />
                                <x-text-input
                                    id="category-name"
                                    wire:model="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    maxlength="100"
                                    autocomplete="off"
                                />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="category-status" value="Status" />
                                <select
                                    id="category-status"
                                    wire:model="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="category-description" value="Description" />
                                <textarea
                                    id="category-description"
                                    wire:model="description"
                                    rows="3"
                                    maxlength="255"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Optional description"
                                ></textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end gap-3">
                            <x-secondary-button type="button" wire:click="cancel">
                                Cancel
                            </x-secondary-button>

                            <x-primary-button type="submit">
                                {{ $editingId ? 'Update Category' : 'Save Category' }}
                            </x-primary-button>
                        </div>
                    </form>
                @endif

                <div class="mt-6 overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($categories as $category)
                                <tr wire:key="category-{{ $category->id }}">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ $category->name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $category->description ?: '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $category->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                            {{ ucfirst($category->status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <button type="button" wire:click="edit({{ $category->id }})" class="text-indigo-600 hover:text-indigo-900">
                                            Edit
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="delete({{ $category->id }})"
                                            wire:confirm="Delete this category?"
                                            class="ml-4 text-red-600 hover:text-red-900"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">
                                        No categories yet. Add your first category to get started.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
