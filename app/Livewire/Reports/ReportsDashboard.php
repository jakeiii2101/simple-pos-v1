<?php

namespace App\Livewire\Reports;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleAdjustment;
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

    /** @return array<int, array{label:string,value:float}> */
    private function dailySalesChart(Carbon $date): array
    {
        $values = array_fill(0, 24, 0.0);

        Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereDoesntHave('adjustment')
            ->whereBetween('completed_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->get(['completed_at', 'total'])
            ->each(function (Sale $sale) use (&$values): void {
                $hour = (int) $sale->completed_at->format('G');
                $values[$hour] += (float) $sale->total;
            });

        return collect($values)
            ->map(fn (float $value, int $hour) => [
                'label' => Carbon::createFromTime($hour)->format('g A'),
                'value' => round($value, 2),
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array{label:string,value:float}> */
    private function monthlySalesChart(Carbon $month): array
    {
        $daysInMonth = $month->daysInMonth;
        $values = array_fill(1, $daysInMonth, 0.0);

        Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereDoesntHave('adjustment')
            ->whereBetween('completed_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get(['completed_at', 'total'])
            ->each(function (Sale $sale) use (&$values): void {
                $day = (int) $sale->completed_at->format('j');
                $values[$day] += (float) $sale->total;
            });

        return collect($values)
            ->map(fn (float $value, int $day) => [
                'label' => (string) $day,
                'value' => round($value, 2),
            ])
            ->values()
            ->all();
    }

    public function render()
    {
        $date = $this->safeDate();
        $month = $this->safeMonth();
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $daily = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereDoesntHave('adjustment')
            ->whereDate('completed_at', $date->toDateString())
            ->selectRaw('COUNT(*) as transactions, COALESCE(SUM(subtotal), 0) as gross_sales, COALESCE(SUM(discount_amount), 0) as discounts, COALESCE(SUM(total), 0) as net_sales')
            ->first();

        $monthly = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereDoesntHave('adjustment')
            ->whereBetween('completed_at', [$monthStart, $monthEnd])
            ->selectRaw('COUNT(*) as transactions, COALESCE(SUM(subtotal), 0) as gross_sales, COALESCE(SUM(discount_amount), 0) as discounts, COALESCE(SUM(total), 0) as net_sales')
            ->first();

        $birTaxSummary = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereDoesntHave('adjustment')
            ->whereBetween('completed_at', [$monthStart, $monthEnd])
            ->selectRaw('COALESCE(SUM(vatable_sales), 0) as vatable_sales, COALESCE(SUM(vat_amount), 0) as vat_amount, COALESCE(SUM(vat_exempt_sales), 0) as vat_exempt_sales, COALESCE(SUM(zero_rated_sales), 0) as zero_rated_sales, COALESCE(SUM(non_vat_sales), 0) as non_vat_sales, COALESCE(SUM(vat_exemption_amount), 0) as vat_exemptions')
            ->first();

        $reversalSummary = SaleAdjustment::query()
            ->whereBetween('processed_at', [$monthStart, $monthEnd])
            ->selectRaw("COUNT(*) as reversal_count, COALESCE(SUM(amount), 0) as reversal_amount, COALESCE(SUM(CASE WHEN type = 'void' THEN amount ELSE 0 END), 0) as void_amount, COALESCE(SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END), 0) as refund_amount")
            ->first();

        $recentReversals = SaleAdjustment::query()
            ->with(['sale', 'authorizedBy'])
            ->whereBetween('processed_at', [$monthStart, $monthEnd])
            ->latest('processed_at')
            ->limit(20)
            ->get();

        $topProducts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->leftJoin('sale_adjustments', 'sale_adjustments.sale_id', '=', 'sales.id')
            ->whereNull('sale_adjustments.id')
            ->whereBetween('sales.completed_at', [$monthStart, $monthEnd])
            ->select(
                'sale_items.product_name',
                'sale_items.sku',
                DB::raw('SUM(sale_items.quantity) as quantity_sold'),
                DB::raw('SUM(sale_items.line_total) as gross_item_sales'),
            )
            ->groupBy('sale_items.product_name', 'sale_items.sku')
            ->orderByDesc('quantity_sold')
            ->limit(10)
            ->get();

        $paymentBreakdown = DB::table('sales')
            ->leftJoin('payments', 'payments.sale_id', '=', 'sales.id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->leftJoin('sale_adjustments', 'sale_adjustments.sale_id', '=', 'sales.id')
            ->whereNull('sale_adjustments.id')
            ->whereBetween('sales.completed_at', [$monthStart, $monthEnd])
            ->selectRaw("COALESCE(payments.method, 'cash') as method, COUNT(*) as transactions, COALESCE(SUM(sales.total), 0) as net_sales")
            ->groupBy(DB::raw("COALESCE(payments.method, 'cash')"))
            ->orderByDesc('net_sales')
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
            'dailyGrossSales' => (float) ($daily->gross_sales ?? 0),
            'dailyDiscounts' => (float) ($daily->discounts ?? 0),
            'dailyNetSales' => (float) ($daily->net_sales ?? 0),
            'dailyTransactions' => (int) ($daily->transactions ?? 0),
            'monthlyGrossSales' => (float) ($monthly->gross_sales ?? 0),
            'monthlyDiscounts' => (float) ($monthly->discounts ?? 0),
            'monthlyNetSales' => (float) ($monthly->net_sales ?? 0),
            'monthlyTransactions' => (int) ($monthly->transactions ?? 0),
            'dailySalesChart' => $this->dailySalesChart($date),
            'monthlySalesChart' => $this->monthlySalesChart($month),
            'topProducts' => $topProducts,
            'paymentBreakdown' => $paymentBreakdown,
            'productCount' => (int) ($inventory->product_count ?? 0),
            'unitsOnHand' => (int) ($inventory->units_on_hand ?? 0),
            'inventoryCost' => (float) ($inventory->inventory_cost ?? 0),
            'lowStockProducts' => $lowStockProducts,
            'vatableSales' => (float) ($birTaxSummary->vatable_sales ?? 0),
            'vatAmount' => (float) ($birTaxSummary->vat_amount ?? 0),
            'vatExemptSales' => (float) ($birTaxSummary->vat_exempt_sales ?? 0),
            'zeroRatedSales' => (float) ($birTaxSummary->zero_rated_sales ?? 0),
            'nonVatSales' => (float) ($birTaxSummary->non_vat_sales ?? 0),
            'vatExemptions' => (float) ($birTaxSummary->vat_exemptions ?? 0),
            'reversalCount' => (int) ($reversalSummary->reversal_count ?? 0),
            'reversalAmount' => (float) ($reversalSummary->reversal_amount ?? 0),
            'voidAmount' => (float) ($reversalSummary->void_amount ?? 0),
            'refundAmount' => (float) ($reversalSummary->refund_amount ?? 0),
            'recentReversals' => $recentReversals,
            'exportFrom' => $monthStart->toDateString(),
            'exportTo' => $monthEnd->toDateString(),
            'reportMonthLabel' => $monthStart->format('F Y'),
        ]);
    }
}
