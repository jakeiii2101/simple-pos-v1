<?php

namespace App\Livewire\Auth;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class CreateAccount extends Component
{
    public string $businessName = '';

    public string $ownerName = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public bool $termsAccepted = false;

    public function submit(): void
    {
        $this->ensureNotRateLimited();

        $validated = $this->validate([
            'businessName' => ['required', 'string', 'min:2', 'max:160'],
            'ownerName' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'same:passwordConfirmation', Password::min(10)->letters()->numbers()],
            'passwordConfirmation' => ['required'],
            'termsAccepted' => ['accepted'],
        ], [
            'password.same' => 'Password confirmation does not match.',
            'termsAccepted.accepted' => 'Please confirm that the information is correct before submitting.',
        ]);

        [$account, $user] = DB::transaction(function () use ($validated): array {
            $account = Account::query()->create([
                'name' => trim($validated['businessName']),
                'slug' => $this->uniqueSlug($validated['businessName']),
                'status' => Account::STATUS_PENDING,
            ]);

            $user = new User;
            $user->account_id = $account->id;
            $user->name = trim($validated['ownerName']);
            $user->email = Str::lower(trim($validated['email']));
            $user->phone = filled($validated['phone']) ? trim($validated['phone']) : null;
            $user->password = Hash::make($validated['password']);
            $user->role = User::ROLE_ADMIN;
            $user->status = User::STATUS_PENDING;
            $user->email_verified_at = now();
            $user->save();

            $account->update(['owner_user_id' => $user->id]);

            $audit = new AuditLog;
            $audit->account_id = $account->id;
            $audit->user_id = $user->id;
            $audit->action = 'account.application_submitted';
            $audit->auditable_type = Account::class;
            $audit->auditable_id = $account->id;
            $audit->description = 'New SniperPOS account application submitted.';
            $audit->metadata = [
                'business_name' => $account->name,
                'owner_email' => $user->email,
            ];
            $audit->ip_address = request()->ip();
            $audit->user_agent = Str::limit((string) request()->userAgent(), 500, '');
            $audit->save();

            return [$account, $user];
        });

        RateLimiter::hit($this->throttleKey(), 3600);

        session()->flash(
            'status',
            'Your SniperPOS account request was submitted. Access will remain locked until the platform owner approves it.'
        );

        $this->redirectRoute('login');
    }

    private function uniqueSlug(string $businessName): string
    {
        $base = Str::slug($businessName) ?: 'business';

        do {
            $slug = $base.'-'.Str::lower(Str::random(6));
        } while (Account::query()->where('slug', $slug)->exists());

        return $slug;
    }

    private function ensureNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 3)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => 'Too many account requests were submitted. Please try again in '.ceil($seconds / 60).' minute(s).',
        ]);
    }

    private function throttleKey(): string
    {
        return 'account-application|'.Str::lower(trim($this->email)).'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.create-account');
    }
}
