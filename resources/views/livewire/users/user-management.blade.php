<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Users</h1>
                <p class="mt-1 text-sm text-gray-500">Manage administrator and cashier accounts.</p>
            </div>
            <button type="button" wire:click="create" class="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-800">
                + Add User
            </button>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($showForm)
            <form wire:submit="save" class="mb-6 rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit User' : 'Add User' }}</h2>

                <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <x-input-label for="user-name" value="Name" />
                        <x-text-input id="user-name" wire:model="name" type="text" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="user-email" value="Email" />
                        <x-text-input id="user-email" wire:model="email" type="email" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="user-role" value="Role" />
                        <select id="user-role" wire:model="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="cashier">Cashier</option>
                            <option value="admin">Admin</option>
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="user-status" value="Status" />
                        <select id="user-status" wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="user-password" :value="$editingId ? 'New Password (optional)' : 'Password'" />
                        <x-text-input id="user-password" wire:model="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="user-password-confirmation" value="Confirm Password" />
                        <x-text-input id="user-password-confirmation" wire:model="passwordConfirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('passwordConfirmation')" class="mt-2" />
                    </div>
                </div>

                @if ($editingId)
                    <p class="mt-3 text-xs text-gray-500">Leave password fields blank to keep the current password.</p>
                @endif

                <div class="mt-5 flex justify-end gap-3">
                    <x-secondary-button type="button" wire:click="cancel">Cancel</x-secondary-button>
                    <x-primary-button type="submit">{{ $editingId ? 'Update User' : 'Save User' }}</x-primary-button>
                </div>
            </form>
        @endif

        <div class="overflow-x-auto rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                {{ $user->name }}
                                @if ($user->id === auth()->id())
                                    <span class="ml-1 text-xs text-gray-400">(You)</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600">{{ $user->email }}</td>
                            <td class="px-4 py-4 text-sm">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->role === 'admin' ? 'bg-indigo-100 text-indigo-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                    {{ ucfirst($user->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right text-sm">
                                <button type="button" wire:click="edit({{ $user->id }})" class="font-medium text-indigo-600 hover:text-indigo-900">Edit</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
