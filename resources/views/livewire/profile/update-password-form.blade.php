<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');
            throw $e;
        }
        Auth::user()->update(['password' => Hash::make($validated['password'])]);
        $this->reset('current_password', 'password', 'password_confirmation');
        $this->dispatch('password-updated');
    }
}; ?>

<section>
    <header>
        <div class="sniper-kicker">Security</div>
        <h2 class="mt-1 font-heading text-xl font-bold text-sniper-navy">Update Password</h2>
        <p class="mt-2 text-sm leading-6 text-sniper-slate">Use a strong, unique password to keep your SniperPOS account secure.</p>
    </header>

    <form wire:submit="updatePassword" class="mt-6 max-w-2xl space-y-5">
        <div><x-input-label for="update_password_current_password" :value="__('Current Password')" /><x-text-input wire:model="current_password" id="update_password_current_password" name="current_password" type="password" class="mt-1.5 block w-full" autocomplete="current-password" /><x-input-error :messages="$errors->get('current_password')" class="mt-2" /></div>
        <div><x-input-label for="update_password_password" :value="__('New Password')" /><x-text-input wire:model="password" id="update_password_password" name="password" type="password" class="mt-1.5 block w-full" autocomplete="new-password" /><x-input-error :messages="$errors->get('password')" class="mt-2" /></div>
        <div><x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" /><x-text-input wire:model="password_confirmation" id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1.5 block w-full" autocomplete="new-password" /><x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" /></div>
        <div class="flex flex-wrap items-center gap-4"><x-primary-button>{{ __('Update Password') }}</x-primary-button><x-action-message on="password-updated">{{ __('Password updated.') }}</x-action-message></div>
    </form>
</section>
