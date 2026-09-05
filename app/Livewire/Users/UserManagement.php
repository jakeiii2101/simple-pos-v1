<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class UserManagement extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public string $role = User::ROLE_CASHIER;

    public string $status = User::STATUS_ACTIVE;

    public bool $showForm = false;

    public function boot(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->isActive() && auth()->user()->isAdmin(),
            403,
        );
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->passwordConfirmation = '';
        $this->role = $user->role;
        $this->status = $user->status;
        $this->showForm = true;
        $this->resetValidation();
    }

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_CASHIER])],
            'status' => ['required', Rule::in([User::STATUS_ACTIVE, User::STATUS_INACTIVE])],
        ];

        if ($this->editingId === null) {
            $rules['password'] = ['required', 'string', 'min:8', 'same:passwordConfirmation'];
            $rules['passwordConfirmation'] = ['required', 'string', 'min:8'];
        } elseif ($this->password !== '' || $this->passwordConfirmation !== '') {
            $rules['password'] = ['required', 'string', 'min:8', 'same:passwordConfirmation'];
            $rules['passwordConfirmation'] = ['required', 'string', 'min:8'];
        }

        $validated = $this->validate($rules, [
            'password.same' => 'Password confirmation does not match.',
        ]);

        if ($this->editingId !== null) {
            $user = User::query()->findOrFail($this->editingId);

            if ($user->id === auth()->id() && $validated['status'] === User::STATUS_INACTIVE) {
                $this->addError('status', 'You cannot deactivate your own account.');
                return;
            }

            if ($user->id === auth()->id() && $validated['role'] !== User::ROLE_ADMIN) {
                $this->addError('role', 'You cannot remove your own admin role.');
                return;
            }

            $user->name = trim($validated['name']);
            $user->email = trim($validated['email']);
            $user->role = $validated['role'];
            $user->status = $validated['status'];

            if ($this->password !== '') {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();
            session()->flash('success', 'User updated successfully.');
        } else {
            $user = new User();
            $user->name = trim($validated['name']);
            $user->email = trim($validated['email']);
            $user->password = Hash::make($validated['password']);
            $user->role = $validated['role'];
            $user->status = $validated['status'];
            $user->email_verified_at = now();
            $user->save();

            session()->flash('success', 'User created successfully.');
        }

        $this->resetForm();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->passwordConfirmation = '';
        $this->role = User::ROLE_CASHIER;
        $this->status = User::STATUS_ACTIVE;
        $this->showForm = false;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.users.user-management', [
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }
}
