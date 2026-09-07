<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);
        $user->fill($validated);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();
        $this->dispatch('profile-updated', name: $user->name);
    }

    public function sendVerification(): void
    {
        $user = Auth::user();
        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));
            return;
        }
        $user->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <div class="sniper-kicker">Personal Details</div>
        <h2 class="mt-1 font-heading text-xl font-bold text-sniper-navy">Profile Information</h2>
        <p class="mt-2 text-sm leading-6 text-sniper-slate">Update your account name and email address used by SniperPOS.</p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 max-w-2xl space-y-5">
        <div><x-input-label for="name" :value="__('Name')" /><x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1.5 block w-full" required autofocus autocomplete="name" /><x-input-error class="mt-2" :messages="$errors->get('name')" /></div>
        <div>
            <x-input-label for="email" :value="__('Email')" /><x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1.5 block w-full" required autocomplete="username" /><x-input-error class="mt-2" :messages="$errors->get('email')" />
            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="sniper-alert-warning mt-3">Your email address is unverified. <button wire:click.prevent="sendVerification" class="font-semibold underline underline-offset-2 hover:text-sniper-red">Send another verification email.</button></div>
                @if (session('status') === 'verification-link-sent')<div class="sniper-alert-success mt-3">A new verification link has been sent to your email address.</div>@endif
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-4"><x-primary-button>{{ __('Save Changes') }}</x-primary-button><x-action-message on="profile-updated">{{ __('Saved.') }}</x-action-message></div>
    </form>
</section>
