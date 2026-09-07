<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    public function deleteUser(Logout $logout): void
    {
        $this->validate(['password' => ['required', 'string', 'current_password']]);
        tap(Auth::user(), $logout(...))->delete();
        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-5">
    <header>
        <div class="text-[11px] font-bold uppercase tracking-[0.22em] text-sniper-red">Danger Zone</div>
        <h2 class="mt-1 font-heading text-xl font-bold text-sniper-navy">Delete Account</h2>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-sniper-slate">Permanently removes your account and associated access. This action cannot be undone.</p>
    </header>

    <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">{{ __('Delete Account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6 sm:p-7">
            <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-red-50 text-xl text-sniper-red ring-1 ring-red-200">!</div>
            <h2 class="mt-4 font-heading text-xl font-bold text-sniper-navy">Are you sure?</h2>
            <p class="mt-2 text-sm leading-6 text-sniper-slate">Once your account is deleted, its data and access are permanently removed. Enter your password to confirm.</p>
            <div class="mt-6"><x-input-label for="password" value="{{ __('Password') }}" /><x-text-input wire:model="password" id="password" name="password" type="password" class="mt-1.5 block w-full" placeholder="Password" /><x-input-error :messages="$errors->get('password')" class="mt-2" /></div>
            <div class="mt-6 flex flex-wrap justify-end gap-3"><x-secondary-button x-on:click="$dispatch('close')">{{ __('Cancel') }}</x-secondary-button><x-danger-button>{{ __('Delete Account') }}</x-danger-button></div>
        </form>
    </x-modal>
</section>
