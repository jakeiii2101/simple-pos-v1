<?php

namespace App\Livewire\Forms;

use App\Models\Account;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            'email' => $this->email,
            'password' => $this->password,
            'status' => User::STATUS_ACTIVE,
        ];

        if (! Auth::attempt($credentials, $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            $user = User::withoutGlobalScope('account')
                ->where('email', $this->email)
                ->first();

            if ($user !== null && Hash::check($this->password, $user->password)) {
                if ($user->isPending()) {
                    throw ValidationException::withMessages([
                        'form.email' => 'Your SniperPOS account request is still awaiting platform-owner approval.',
                    ]);
                }

                if ($user->status === User::STATUS_INACTIVE) {
                    throw ValidationException::withMessages([
                        'form.email' => 'This SniperPOS user account is inactive.',
                    ]);
                }
            }

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        $user = Auth::user();

        if (! $user->isPlatformOwner()) {
            $account = $user->account;

            if ($account === null || $account->status !== Account::STATUS_ACTIVE) {
                Auth::logout();

                $message = match ($account?->status) {
                    Account::STATUS_SUSPENDED => 'This SniperPOS business account is currently suspended.',
                    Account::STATUS_REJECTED => 'This SniperPOS business account request was not approved.',
                    Account::STATUS_PENDING => 'Your SniperPOS account request is still awaiting platform-owner approval.',
                    default => 'This SniperPOS business account is not active.',
                };

                throw ValidationException::withMessages([
                    'form.email' => $message,
                ]);
            }
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
