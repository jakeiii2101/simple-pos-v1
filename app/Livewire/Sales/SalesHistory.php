<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SalesHistory extends Component
{
    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function boot(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->isActive() && auth()->user()->isAdmin(),
            403,
        );
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->dateFrom = '';
        $this->dateTo = '';
    }

    public function render()
    {
        $query = Sale::query()
            ->with(['user', 'items'])
            ->where('status', Sale::STATUS_COMPLETED)
            ->when(trim($this->search) !== '', function ($query): void {
                $term = trim($this->search);

                $query->where(function ($query) use ($term): void {
                    $query->where('sale_number', 'like', '%'.$term.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$term.'%'));
                });
            })
            ->when($this->dateFrom !== '', fn ($query) => $query->whereDate('completed_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($query) => $query->whereDate('completed_at', '<=', $this->dateTo));

        $summary = (clone $query)
            ->selectRaw('COUNT(*) as transaction_count, COALESCE(SUM(total), 0) as gross_sales')
            ->first();

        $itemsSold = (clone $query)
            ->withSum('items as items_sold', 'quantity')
            ->get()
            ->sum('items_sold');

        return view('livewire.sales.sales-history', [
            'sales' => $query->latest('completed_at')->limit(100)->get(),
            'transactionCount' => (int) ($summary->transaction_count ?? 0),
            'grossSales' => (float) ($summary->gross_sales ?? 0),
            'itemsSold' => (int) $itemsSold,
        ]);
    }
}
