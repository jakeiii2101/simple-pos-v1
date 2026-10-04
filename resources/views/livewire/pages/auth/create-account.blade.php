<?php

use App\Models\AccountRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $businessName = '';
    public string $ownerName = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $submitted = false;

    public function submit(): void
    {
        $key = 'account-request:'.strtolower(trim($this->email)).'|'.request()->ip();

        if (! RateLimiter::attempt($key, 3, fn () => true, 3600)) {
            throw ValidationException::withMessages([
                'email' => 'Too many account requests were submitted. Please try again later.',
            ]);
        }

        $validated = $this->validate([
            'businessName' => ['required', 'string', 'min:2', 'max:200'],
            'ownerName' => ['required', 'string', 'min:2', 'max:150'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('account_requests', 'email')
                    ->where(fn ($query) => $query->where('status', AccountRequest::STATUS_PENDING)),
            ],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.unique' => 'This email already has an account or a pending account request.',
        ]);

        AccountRequest::query()->create([
            'business_name' => trim($validated['businessName']),
            'owner_name' => trim($validated['ownerName']),
            'email' => strtolower(trim($validated['email'])),
            'password_hash' => Hash::make($validated['password']),
            'status' => AccountRequest::STATUS_PENDING,
        ]);

        $this->reset(['businessName', 'ownerName', 'email', 'password', 'password_confirmation']);
        $this->submitted = true;
    }
}; ?>

<div>
    @if ($submitted)
        <div class="text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>
            </div>
            <div class="sniper-kicker mt-5">Request submitted</div>
            <h2 class="mt-1 font-heading text-2xl font-bold tracking-tight text-sniper-navy">Your SniperPOS account is pending approval.</h2>
            <p class="mt-3 text-sm leading-6 text-sniper-slate">
                Your business workspace will be created only after the SniperPOS platform owner approves your request.
                Once approved, use the email and password you submitted to log in.
            </p>
            <div class="mt-6 grid gap-3">
                <a href="{{ route('login', [], false) }}" class="sniper-btn-primary justify-center">Go to Login</a>
                <button type="button" wire:click="$set('submitted', false)" class="sniper-btn-secondary justify-center">Submit another request</button>
            </div>
        </div>
    @else
        <div class="mb-6">
            <div class="sniper-kicker">New business workspace</div>
            <h2 class="mt-1 font-heading text-2xl font-bold tracking-tight text-sniper-navy">Create a SniperPOS account</h2>
            <p class="mt-2 text-sm leading-6 text-sniper-slate">
                Submit your business details for approval. Each approved business receives a separate POS workspace for its own products, inventory, sales, users, reports, and settings.
            </p>
        </div>

        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-900">
            Accounts are not activated automatically. The SniperPOS platform owner reviews every request before access is granted.
        </div>

        <form wire:submit="submit" class="space-y-5">
            <div>
                <x-input-label for="business-name" value="Business / Store Name" />
                <x-text-input wire:model="businessName" id="business-name" class="mt-1.5 block w-full" type="text" maxlength="200" required autofocus placeholder="Example: JKH Mini Mart" />
                <x-input-error :messages="$errors->get('businessName')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="owner-name" value="Owner / Administrator Name" />
                <x-text-input wire:model="ownerName" id="owner-name" class="mt-1.5 block w-full" type="text" maxlength="150" required autocomplete="name" placeholder="Your full name" />
                <x-input-error :messages="$errors->get('ownerName')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="account-email" value="Email" />
                <x-text-input wire:model="email" id="account-email" class="mt-1.5 block w-full" type="email" maxlength="255" required autocomplete="email" placeholder="you@example.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="account-password" value="Password" />
                <x-text-input wire:model="password" id="account-password" class="mt-1.5 block w-full" type="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="account-password-confirmation" value="Confirm Password" />
                <x-text-input wire:model="password_confirmation" id="account-password-confirmation" class="mt-1.5 block w-full" type="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <x-primary-button class="w-full justify-center py-3">Submit for Approval</x-primary-button>

            <div class="text-center text-sm text-sniper-slate">
                Already approved?
                <a href="{{ route('login', [], false) }}" class="font-semibold text-sniper-navy hover:text-sniper-red">Log in</a>
            </div>
        </form>
    @endif
</div>
