<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleAdjustment;
use App\Models\SaleRefund;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsExportController extends Controller
{
    public function sales(Request $request): StreamedResponse
    {
        [$from, $to] = $this->validatedRange($request);

        $sales = Sale::query()
            ->with(['payment', 'adjustment', 'refunds'])
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('completed_at')
            ->orderBy('id');

        return response()->streamDownload(function () use ($sales): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Invoice Number', 'Sale Date', 'Buyer Name', 'Buyer TIN', 'Gross Sales', 'Discount',
                'VAT Exemption', 'VATable Sales', 'VAT Amount', 'VAT-Exempt Sales', 'Zero-Rated Sales',
                'Non-VAT Sales', 'Original Net Total', 'Partial Refund Total', 'Effective Net Total',
                'Payment Method', 'Status',
            ], ',', '"', '');

            foreach ($sales->lazy(500) as $sale) {
                fputcsv($output, [
                    $this->csvText($sale->invoice_number ?? $sale->sale_number),
                    $sale->completed_at->format('Y-m-d H:i:s'),
                    $this->csvText($sale->buyer_name),
                    $this->csvText($sale->buyer_tin),
                    $sale->subtotal,
                    $sale->discount_amount,
                    $sale->vat_exemption_amount,
                    $sale->vatable_sales,
                    $sale->vat_amount,
                    $sale->vat_exempt_sales,
                    $sale->zero_rated_sales,
                    $sale->non_vat_sales,
                    $sale->total,
                    $sale->refunds->sum('refund_amount'),
                    $sale->adjustment ? 0 : round((float) $sale->total - (float) $sale->refunds->sum('refund_amount'), 2),
                    strtoupper($sale->payment?->method ?? 'cash'),
                    strtoupper($sale->adjustment?->type ?? ($sale->refunds->isNotEmpty() ? 'partial refund' : 'active')),
                ], ',', '"', '');
            }

            fclose($output);
        }, 'bir-sales-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function reversals(Request $request): StreamedResponse
    {
        [$from, $to] = $this->validatedRange($request);

        $adjustments = SaleAdjustment::query()
            ->with(['sale', 'authorizedBy'])
            ->whereBetween('processed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('processed_at')
            ->orderBy('id');

        $partialRefunds = SaleRefund::query()
            ->with(['sale', 'authorizedBy', 'items.saleItem'])
            ->whereBetween('processed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('processed_at')
            ->orderBy('id')
            ->get();

        return response()->streamDownload(function () use ($adjustments, $partialRefunds): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Reversal Date', 'Type', 'Invoice Number', 'Original Sale Date', 'Amount',
                'Reference', 'Reason', 'Returned Items', 'Inventory Restocked', 'Authorized By',
            ], ',', '"', '');

            foreach ($adjustments->lazy(500) as $adjustment) {
                fputcsv($output, [
                    $adjustment->processed_at->format('Y-m-d H:i:s'),
                    strtoupper($adjustment->type),
                    $this->csvText($adjustment->sale->invoice_number ?? $adjustment->sale->sale_number),
                    $adjustment->sale->completed_at->format('Y-m-d H:i:s'),
                    $adjustment->amount,
                    '',
                    $this->csvText($adjustment->reason),
                    '',
                    $adjustment->inventory_restocked ? 'YES' : 'NO',
                    $this->csvText($adjustment->authorizedBy->name),
                ], ',', '"', '');
            }

            foreach ($partialRefunds as $refund) {
                $items = $refund->items->map(fn ($item) => $item->saleItem->product_name.' x'.$item->quantity)->implode('; ');
                fputcsv($output, [
                    $refund->processed_at->format('Y-m-d H:i:s'),
                    'PARTIAL REFUND',
                    $this->csvText($refund->sale->invoice_number ?? $refund->sale->sale_number),
                    $refund->sale->completed_at->format('Y-m-d H:i:s'),
                    $refund->refund_amount,
                    $refund->refund_number,
                    $this->csvText($refund->reason),
                    $this->csvText($items),
                    $refund->inventory_restocked ? 'YES' : 'NO',
                    $this->csvText($refund->authorizedBy->name),
                ], ',', '"', '');
            }

            fclose($output);
        }, 'void-refund-register-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** @return array{Carbon, Carbon} */
    private function validatedRange(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return [Carbon::parse($validated['from']), Carbon::parse($validated['to'])];
    }

    private function csvText(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
