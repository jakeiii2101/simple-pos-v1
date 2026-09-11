<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use App\Models\SaleRefund;
use App\Support\PartialRefundService;
use Livewire\Component;

class PartialRefundPanel extends Component
{
    public Sale $sale;

    /** @var array<int, int|string> */
    public array $quantities = [];

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
        $this->resetQuantities();
    }

    public function process(PartialRefundService $service): void
    {
        $rules = [
            'quantities' => ['array'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'restockInventory' => ['boolean'],
            'authorizationPassword' => ['required', 'current_password'],
            'confirmed' => ['accepted'],
        ];

        foreach ($this->sale->items as $item) {
            $rules['quantities.'.$item->id] = ['nullable', 'integer', 'min:0'];
        }

        $validated = $this->validate($rules, [
            'authorizationPassword.current_password' => 'The administrator password is incorrect.',
            'confirmed.accepted' => 'Confirm that the partial refund details are correct.',
        ]);

        $service->refund(
            $this->sale,
            auth()->user(),
            $validated['quantities'],
            $validated['reason'],
            $validated['restockInventory'],
        );

        $this->sale->refresh();
        $this->reset(['reason', 'authorizationPassword', 'confirmed']);
        $this->resetQuantities();
        session()->flash('partial-refund-success', 'The partial refund was recorded successfully.');
    }

    public function render()
    {
        $this->sale->load(['items', 'adjustment', 'refunds.authorizedBy', 'refunds.items']);

        $refundedQuantities = $this->sale->refunds
            ->flatMap(fn (SaleRefund $refund) => $refund->items)
            ->groupBy('sale_item_id')
            ->map(fn ($items) => (int) $items->sum('quantity'));

        return view('livewire.sales.partial-refund-panel', compact('refundedQuantities'));
    }

    private function resetQuantities(): void
    {
        $this->sale->loadMissing('items');
        $this->quantities = $this->sale->items->mapWithKeys(fn ($item) => [$item->id => 0])->all();
    }
}
