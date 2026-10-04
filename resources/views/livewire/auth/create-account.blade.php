<div>
    <div class="mb-6">
        <div class="sniper-kicker">Request Access</div>
        <h2 class="mt-1 font-heading text-2xl font-bold tracking-tight text-sniper-navy">Create your SniperPOS account</h2>
        <p class="mt-2 text-sm leading-6 text-sniper-slate">
            Register your business and owner account. Your POS workspace stays locked until the SniperPOS platform owner reviews and approves your request.
        </p>
    </div>

    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
        <strong>Approval required.</strong> Submitting this form does not activate the POS immediately. You will be able to log in only after your account is approved.
    </div>

    <form wire:submit="submit" class="space-y-5">
        <div>
            <x-input-label for="business-name" value="Business / Store Name" />
            <x-text-input id="business-name" wire:model="businessName" type="text" class="mt-1.5 block w-full" maxlength="160" required autofocus placeholder="Your business name" />
            <x-input-error :messages="$errors->get('businessName')" class="mt-2" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="owner-name" value="Owner / Administrator Name" />
                <x-text-input id="owner-name" wire:model="ownerName" type="text" class="mt-1.5 block w-full" maxlength="100" required placeholder="Full name" />
                <x-input-error :messages="$errors->get('ownerName')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="phone" value="Contact Number" />
                <x-text-input id="phone" wire:model="phone" type="text" class="mt-1.5 block w-full" maxlength="40" placeholder="Optional" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="email" value="Email Address" />
            <x-text-input id="email" wire:model="email" type="email" class="mt-1.5 block w-full" maxlength="255" required autocomplete="email" placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
            <p class="mt-1.5 text-xs text-sniper-slate">This becomes the first administrator login for your POS workspace.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="password" value="Password" />
                <x-text-input id="password" wire:model="password" type="password" class="mt-1.5 block w-full" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password-confirmation" value="Confirm Password" />
                <x-text-input id="password-confirmation" wire:model="passwordConfirmation" type="password" class="mt-1.5 block w-full" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('passwordConfirmation')" class="mt-2" />
            </div>
        </div>

        <p class="text-xs leading-5 text-sniper-slate">Use at least 10 characters with letters and numbers.</p>

        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <input wire:model="termsAccepted" type="checkbox" class="mt-1 rounded border-slate-300 text-sniper-red focus:ring-sniper-red">
            <span class="text-sm leading-6 text-sniper-slate">
                I confirm that the business and contact information above is accurate and I understand that SniperPOS access requires manual approval.
            </span>
        </label>
        <x-input-error :messages="$errors->get('termsAccepted')" class="-mt-3" />

        <x-primary-button class="w-full py-3" wire:loading.attr="disabled" wire:target="submit">
            <span wire:loading.remove wire:target="submit">Submit Account Request</span>
            <span wire:loading wire:target="submit">Submitting...</span>
        </x-primary-button>

        <div class="text-center text-sm text-sniper-slate">
            Already approved?
            <a href="{{ route('login', [], false) }}" class="font-semibold text-sniper-navy hover:text-sniper-red">Log in to SniperPOS</a>
        </div>
    </form>
</div>
