<?php

namespace App\Livewire\Reports;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ReportsDashboard extends Component
{
    public string $reportDate = '';

    public string $reportMonth = '';

    public function mount(): void
    {
        $this->reportDate = now()->toDateString();
        $this->reportMonth = now()->format('Y-m');
    }

    public function boot(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->isActive() && auth()->user()->isAdmin(),
            403,
        );
    }

    private function safeDate(): Carbon
    {
        $validator = Validator::make(
            ['reportDate' => $this->reportDate],
            ['reportDate' => ['required', 'date_format:Y-m-d']],
        );

        if ($validator->fails()) {
            return now()->startOfDay();
        }

        return Carbon::createFromFormat('Y-m-d', $this->reportDate)->startOfDay();
    }

    private function safeMonth(): Carbon
    {
        $validator = Validator::make(
            ['reportMonth' => $this->reportMonth],
            ['reportMonth' => ['required', 'date_format:Y-m']],
        );

        if ($validator->fails()) {
            return now()->startOfMonth();
        }

        return Carbon::createFromFormat('Y-m', $this->reportMonth)->startOfMonth();
    }

    public function render()
    {
        $date = $this->safeDate();
        $month = $this->safeMonth();

        $daily = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereDate('completed_at', $date->toDateString())
            ->selectRaw('COUNT(*) as transactions, COALESCE(SUM(total), 0) as sales')
            ->first();

        $monthly = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereBetween('completed_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth(),
            ])
            ->selectRaw('COUNT(*) as transactions, COALESCE(SUM(total), 0) as sales')
            ->first();

        $topProducts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereBetween('sales.completed_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth(),
            ])
            ->select(
                'sale_items.product_name',
                'sale_items.sku',
                DB::raw('SUM(sale_items.quantity) as quantity_sold'),
                DB::raw('SUM(sale_items.line_total) as sales_total'),
            )
            ->groupBy('sale_items.product_name', 'sale_items.sku')
            ->orderByDesc('quantity_sold')
            ->limit(10)
            ->get();

        $inventory = Product::query()
            ->selectRaw('COUNT(*) as product_count, COALESCE(SUM(stock_quantity), 0) as units_on_hand, COALESCE(SUM(stock_quantity * cost_price), 0) as inventory_cost')
            ->first();

        $lowStockProducts = Product::query()
            ->whereColumn('stock_quantity', '<=', 'low_stock_level')
            ->orderBy('stock_quantity')
            ->orderBy('name')
            ->limit(20)
            ->get();

        return view('livewire.reports.reports-dashboard', [
            'dailySales' => (float) ($daily->sales ?? 0),
            'dailyTransactions' => (int) ($daily->transactions ?? 0),
            'monthlySales' => (float) ($monthly->sales ?? 0),
            'monthlyTransactions' => (int) ($monthly->transactions ?? 0),
            'topProducts' => $topProducts,
            'productCount' => (int) ($inventory->product_count ?? 0),
            'unitsOnHand' => (int) ($inventory->units_on_hand ?? 0),
            'inventoryCost' => (float) ($inventory->inventory_cost ?? 0),
            'lowStockProducts' => $lowStockProducts,
        ]);
    }
}
