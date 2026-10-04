<?php

namespace App\Livewire\Platform;

use App\Models\AccountRequest;
use App\Models\Business;
use App\Models\PrinterSetting;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AccountApprovals extends Component
{
    public ?int $rejectingId = null;

    public string $rejectionReason = '';

    public function boot(): void
    {
        abort_unless(
            auth()->check()
                && auth()->user()->isActive()
                && auth()->user()->isPlatformOwner(),
            403,
        );
    }

    public function approve(int $requestId): void
    {
        DB::transaction(function () use ($requestId): void {
            $request = AccountRequest::query()->lockForUpdate()->findOrFail($requestId);

            if (! $request->isPending()) {
                throw ValidationException::withMessages([
                    'approval' => 'This account request has already been processed.',
                ]);
            }

            if (User::query()->where('email', $request->email)->exists()) {
                throw ValidationException::withMessages([
                    'approval' => 'An active account already uses this email address.',
                ]);
            }

            $business = Business::query()->create([
                'name' => $request->business_name,
                'slug' => $this->uniqueBusinessSlug($request->business_name),
                'status' => Business::STATUS_ACTIVE,
            ]);

            $user = User::query()->create([
                'business_id' => $business->id,
                'name' => $request->owner_name,
                'email' => $request->email,
                'password' => $request->password_hash,
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
                'is_platform_owner' => false,
            ]);

            PrinterSetting::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'paper_width_mm' => PrinterSetting::DEFAULT_PAPER_WIDTH_MM,
                'content_padding_mm' => PrinterSetting::DEFAULT_CONTENT_PADDING_MM,
                'font_size_px' => PrinterSetting::DEFAULT_FONT_SIZE_PX,
                'show_logo' => true,
            ]);

            $request->update([
                'status' => AccountRequest::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            Audit::record(
                'platform.account_request.approved',
                $user,
                'Customer SniperPOS account approved.',
                [
                    'business_id' => $business->id,
                    'business_name' => $business->name,
                    'approved_email' => $user->email,
                    'account_request_id' => $request->id,
                ],
            );
        });

        session()->flash('success', 'Account approved. The customer can now log in to their separate SniperPOS workspace.');
    }

    public function startReject(int $requestId): void
    {
        $request = AccountRequest::query()->findOrFail($requestId);
        abort_unless($request->isPending(), 422);

        $this->rejectingId = $request->id;
        $this->rejectionReason = '';
        $this->resetValidation();
    }

    public function reject(): void
    {
        $validated = $this->validate([
            'rejectingId' => ['required', 'integer'],
            'rejectionReason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        DB::transaction(function () use ($validated): void {
            $request = AccountRequest::query()
                ->lockForUpdate()
                ->findOrFail((int) $validated['rejectingId']);

            if (! $request->isPending()) {
                throw ValidationException::withMessages([
                    'rejectionReason' => 'This account request has already been processed.',
                ]);
            }

            $request->update([
                'status' => AccountRequest::STATUS_REJECTED,
                'rejected_at' => now(),
                'rejection_reason' => trim($validated['rejectionReason']),
                'approved_by' => auth()->id(),
            ]);

            Audit::record(
                'platform.account_request.rejected',
                null,
                'Customer SniperPOS account request rejected.',
                [
                    'account_request_id' => $request->id,
                    'business_name' => $request->business_name,
                    'email' => $request->email,
                ],
            );
        });

        $this->rejectingId = null;
        $this->rejectionReason = '';
        session()->flash('success', 'Account request rejected.');
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
        $this->rejectionReason = '';
        $this->resetValidation();
    }

    private function uniqueBusinessSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'business';
        $slug = $base;
        $counter = 2;

        while (Business::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function render()
    {
        return view('livewire.platform.account-approvals', [
            'pendingRequests' => AccountRequest::query()
                ->where('status', AccountRequest::STATUS_PENDING)
                ->oldest()
                ->get(),
            'recentRequests' => AccountRequest::query()
                ->whereIn('status', [
                    AccountRequest::STATUS_APPROVED,
                    AccountRequest::STATUS_REJECTED,
                ])
                ->latest('updated_at')
                ->limit(25)
                ->get(),
        ]);
    }
}
