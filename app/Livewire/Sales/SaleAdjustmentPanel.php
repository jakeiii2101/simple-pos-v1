<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use App\Models\SaleAdjustment;
use App\Support\SaleReversalService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SaleAdjustmentPanel extends Component
{
    public Sale $sale;

    public string $reversalType = SaleAdjustment::TYPE_VOID;

    public string $reason = '';

    public bool $restockInventory = true;

    public string $authorizationPassword = '';

    public bool $confirmed = false;

    public function boot(): void
    {
        abort_unless(auth()->check() && auth()->user()->isActive() && auth()->user()->isAdmin(), 403);
    }

    public function mount(Sale $sale): void
    {
        $this->sale = $sale;
        $this->reversalType = $sale->completed_at->isToday()
            ? SaleAdjustment::TYPE_VOID
            : SaleAdjustment::TYPE_REFUND;
    }

    public function process(SaleReversalService $service): void
    {
        $validated = $this->validate([
            'reversalType' => ['required', Rule::in([SaleAdjustment::TYPE_VOID, SaleAdjustment::TYPE_REFUND])],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'restockInventory' => ['boolean'],
            'authorizationPassword' => ['required', 'current_password'],
            'confirmed' => ['accepted'],
        ], [
            'authorizationPassword.current_password' => 'The administrator password is incorrect.',
            'confirmed.accepted' => 'Confirm that the reversal details are correct.',
        ]);

        $service->reverse(
            $this->sale,
            auth()->user(),
            $validated['reversalType'],
            $validated['reason'],
            $validated['restockInventory'],
        );

        $this->sale->refresh()->load(['adjustment.authorizedBy']);
        $this->reset(['reason', 'authorizationPassword', 'confirmed']);
        session()->flash('adjustment-success', 'The sale reversal was recorded successfully.');
    }

    public function render()
    {
        $this->sale->loadMissing(['adjustment.authorizedBy']);

        return view('livewire.sales.sale-adjustment-panel');
    }
}
