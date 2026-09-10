<?php

namespace App\Livewire\Dashboard;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DashboardOverview extends Component
{
    public function boot(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->isActive(),
            403,
        );
    }

    public function render()
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $todayQuery = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereDoesntHave('adjustment')
            ->whereBetween('completed_at', [$todayStart, $todayEnd]);

        $today = (clone $todayQuery)
            ->selectRaw('COUNT(*) as transactions, COALESCE(SUM(subtotal), 0) as gross_sales, COALESCE(SUM(discount_amount), 0) as discounts, COALESCE(SUM(total), 0) as net_sales')
            ->first();

        $itemsSoldToday = (int) DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->leftJoin('sale_adjustments', 'sale_adjustments.sale_id', '=', 'sales.id')
            ->whereNull('sale_adjustments.id')
            ->whereBetween('sales.completed_at', [$todayStart, $todayEnd])
            ->sum('sale_items.quantity');

        $lowStockCount = Product::query()
            ->whereColumn('stock_quantity', '<=', 'low_stock_level')
            ->count();

        $recentSales = Sale::query()
            ->with(['user', 'payment', 'adjustment'])
            ->where('status', Sale::STATUS_COMPLETED)
            ->latest('completed_at')
            ->limit(6)
            ->get();

        $topProductsToday = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->leftJoin('sale_adjustments', 'sale_adjustments.sale_id', '=', 'sales.id')
            ->whereNull('sale_adjustments.id')
            ->whereBetween('sales.completed_at', [$todayStart, $todayEnd])
            ->select(
                'sale_items.product_name',
                'sale_items.sku',
                DB::raw('SUM(sale_items.quantity) as quantity_sold'),
            )
            ->groupBy('sale_items.product_name', 'sale_items.sku')
            ->orderByDesc('quantity_sold')
            ->limit(5)
            ->get();

        return view('livewire.dashboard.dashboard-overview', [
            'salesToday' => (float) ($today->net_sales ?? 0),
            'grossSalesToday' => (float) ($today->gross_sales ?? 0),
            'discountsToday' => (float) ($today->discounts ?? 0),
            'transactionsToday' => (int) ($today->transactions ?? 0),
            'itemsSoldToday' => $itemsSoldToday,
            'lowStockCount' => $lowStockCount,
            'recentSales' => $recentSales,
            'topProductsToday' => $topProductsToday,
        ]);
    }
}
