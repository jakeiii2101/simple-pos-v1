<?php

use App\Livewire\Forms\LoginForm;
use App\Support\Audit;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();
        Session::forget('url.intended');

        Audit::record(
            'auth.login',
            auth()->user(),
            'User logged in successfully.',
        );

        $this->redirect('/dashboard', navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <div class="sniper-kicker">Welcome Back</div>
        <h2 class="mt-1 font-heading text-2xl font-bold tracking-tight text-sniper-navy">Log in to SniperPOS</h2>
        <p class="mt-2 text-sm leading-6 text-sniper-slate">Access your dashboard, sales, inventory, and reports.</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <div><x-input-label for="email" :value="__('Email')" /><x-text-input wire:model="form.email" id="email" class="mt-1.5 block w-full" type="email" name="email" required autofocus autocomplete="username" placeholder="you@example.com" /><x-input-error :messages="$errors->get('form.email')" class="mt-2" /></div>
        <div><x-input-label for="password" :value="__('Password')" /><x-text-input wire:model="form.password" id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password" /><x-input-error :messages="$errors->get('form.password')" class="mt-2" /></div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <label for="remember" class="inline-flex items-center gap-2 text-sm text-sniper-slate">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-slate-300 text-sniper-red shadow-sm focus:ring-sniper-red/30" name="remember">
                <span>{{ __('Remember me') }}</span>
            </label>
            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-sniper-navy hover:text-sniper-red focus:outline-none focus:ring-2 focus:ring-sniper-red/20" href="{{ route('password.request') }}" wire:navigate>{{ __('Forgot your password?') }}</a>
            @endif
        </div>

        <x-primary-button class="w-full py-3">{{ __('Log in') }}</x-primary-button>
        <a href="/" class="block text-center text-sm font-medium text-sniper-slate hover:text-sniper-red">← Back to SniperPOS home</a>
    </form>
</div>
