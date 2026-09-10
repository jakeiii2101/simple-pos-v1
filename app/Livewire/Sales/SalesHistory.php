<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SalesHistory extends Component
{
    public string $search = '';

    public string $preset = 'all';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function boot(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->isActive() && auth()->user()->isAdmin(),
            403,
        );
    }

    public function setPreset(string $preset): void
    {
        $validated = Validator::make(
            ['preset' => $preset],
            ['preset' => ['required', Rule::in(['all', 'today', 'yesterday', 'this_week', 'this_month', 'custom'])]],
        )->validate();

        $this->preset = $validated['preset'];
        $this->resetErrorBag(['dateFrom', 'dateTo']);

        switch ($this->preset) {
            case 'today':
                $this->dateFrom = now()->toDateString();
                $this->dateTo = now()->toDateString();
                break;
            case 'yesterday':
                $this->dateFrom = now()->subDay()->toDateString();
                $this->dateTo = $this->dateFrom;
                break;
            case 'this_week':
                $this->dateFrom = now()->startOfWeek()->toDateString();
                $this->dateTo = now()->endOfWeek()->toDateString();
                break;
            case 'this_month':
                $this->dateFrom = now()->startOfMonth()->toDateString();
                $this->dateTo = now()->endOfMonth()->toDateString();
                break;
            case 'all':
                $this->dateFrom = '';
                $this->dateTo = '';
                break;
            case 'custom':
                $this->validateCustomDates();
                break;
        }
    }

    public function updatedDateFrom(): void
    {
        $this->preset = 'custom';
        $this->validateCustomDates();
    }

    public function updatedDateTo(): void
    {
        $this->preset = 'custom';
        $this->validateCustomDates();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->preset = 'all';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetValidation();
    }

    private function validateCustomDates(): bool
    {
        $validator = Validator::make(
            [
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
            ],
            [
                'dateFrom' => ['nullable', 'date_format:Y-m-d'],
                'dateTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
            ],
            [
                'dateTo.after_or_equal' => 'The To date must be the same as or after the From date.',
            ],
        );

        $this->resetErrorBag(['dateFrom', 'dateTo']);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return false;
        }

        return true;
    }

    /** @return array{0:?string,1:?string} */
    private function safeDateRange(): array
    {
        $validator = Validator::make(
            [
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
            ],
            [
                'dateFrom' => ['nullable', 'date_format:Y-m-d'],
                'dateTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
            ],
        );

        if ($validator->fails()) {
            return [null, null];
        }

        return [
            $this->dateFrom !== '' ? $this->dateFrom : null,
            $this->dateTo !== '' ? $this->dateTo : null,
        ];
    }

    public function render()
    {
        [$dateFrom, $dateTo] = $this->safeDateRange();

        $query = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->when(trim($this->search) !== '', function ($query): void {
                $term = trim($this->search);

                $query->where(function ($query) use ($term): void {
                    $query->where('sale_number', 'like', '%'.$term.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$term.'%'));
                });
            })
            ->when($dateFrom !== null, fn ($query) => $query->whereDate('completed_at', '>=', $dateFrom))
            ->when($dateTo !== null, fn ($query) => $query->whereDate('completed_at', '<=', $dateTo));

        $activeQuery = (clone $query)->whereDoesntHave('adjustment');

        $summary = (clone $activeQuery)
            ->selectRaw('COUNT(*) as transaction_count, COALESCE(SUM(subtotal), 0) as gross_sales, COALESCE(SUM(discount_amount), 0) as discounts, COALESCE(SUM(total), 0) as net_sales')
            ->first();

        $itemsSold = (clone $activeQuery)
            ->withSum('items as items_sold', 'quantity')
            ->get()
            ->sum('items_sold');

        return view('livewire.sales.sales-history', [
            'sales' => (clone $query)
                ->with(['user', 'items', 'payment', 'adjustment'])
                ->latest('completed_at')
                ->limit(100)
                ->get(),
            'transactionCount' => (int) ($summary->transaction_count ?? 0),
            'grossSales' => (float) ($summary->gross_sales ?? 0),
            'discounts' => (float) ($summary->discounts ?? 0),
            'netSales' => (float) ($summary->net_sales ?? 0),
            'itemsSold' => (int) $itemsSold,
        ]);
    }
}
