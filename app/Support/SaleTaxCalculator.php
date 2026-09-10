<?php

namespace App\Support;

use App\Models\BirSetting;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Validation\ValidationException;

class SaleTaxCalculator
{
    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<string, mixed>
     */
    public function calculate(array $lines, BirSetting $setting, ?string $discountType, float $discountValue): array
    {
        $subtotal = round(collect($lines)->sum('line_total'), 2);
        $regulated = in_array($discountType, [Sale::DISCOUNT_SENIOR, Sale::DISCOUNT_PWD], true);
        $generalDiscount = $regulated ? 0.0 : $this->generalDiscount($subtotal, $discountType, $discountValue);
        $remainingDiscount = $generalDiscount;
        $vatRate = (float) $setting->vat_rate / 100;
        $results = [];
        $totals = [
            'discount_amount' => 0.0,
            'vat_exemption_amount' => 0.0,
            'vatable_sales' => 0.0,
            'vat_amount' => 0.0,
            'vat_exempt_sales' => 0.0,
            'zero_rated_sales' => 0.0,
            'non_vat_sales' => 0.0,
            'total' => 0.0,
        ];

        foreach ($lines as $index => $line) {
            $gross = round((float) $line['line_total'], 2);
            $eligible = (bool) ($line['is_senior_pwd_discount_eligible'] ?? false)
                && ($line['tax_type'] ?? Product::TAX_VATABLE) === Product::TAX_VATABLE;
            $lineDiscount = 0.0;
            $vatExemption = 0.0;

            if ($regulated && $eligible) {
                $discountBase = $setting->tax_type === BirSetting::TAX_TYPE_VAT
                    ? round($gross / (1 + $vatRate), 2)
                    : $gross;
                $vatExemption = round($gross - $discountBase, 2);
                $lineDiscount = round($discountBase * 0.20, 2);
                $net = round($discountBase - $lineDiscount, 2);
            } else {
                $lineDiscount = $index === array_key_last($lines)
                    ? $remainingDiscount
                    : round($generalDiscount * ($subtotal > 0 ? $gross / $subtotal : 0), 2);
                $remainingDiscount = round($remainingDiscount - $lineDiscount, 2);
                $net = round($gross - $lineDiscount, 2);
            }

            $lineVat = 0.0;
            if ($setting->tax_type === BirSetting::TAX_TYPE_NON_VAT) {
                $totals['non_vat_sales'] += $net;
            } elseif ($regulated && $eligible) {
                $totals['vat_exempt_sales'] += $net;
            } elseif (($line['tax_type'] ?? Product::TAX_VATABLE) === Product::TAX_VAT_EXEMPT) {
                $totals['vat_exempt_sales'] += $net;
            } elseif (($line['tax_type'] ?? Product::TAX_VATABLE) === Product::TAX_ZERO_RATED) {
                $totals['zero_rated_sales'] += $net;
            } else {
                $base = round($net / (1 + $vatRate), 2);
                $lineVat = round($net - $base, 2);
                $totals['vatable_sales'] += $base;
                $totals['vat_amount'] += $lineVat;
            }

            $totals['discount_amount'] += $lineDiscount;
            $totals['vat_exemption_amount'] += $vatExemption;
            $totals['total'] += $net;
            $results[] = $line + [
                'discount_amount' => $lineDiscount,
                'vat_amount' => $lineVat,
                'vat_exemption_amount' => $vatExemption,
                'net_total' => $net,
            ];
        }

        foreach ($totals as $key => $value) {
            $totals[$key] = round($value, 2);
        }

        return ['subtotal' => $subtotal, 'lines' => $results] + $totals;
    }

    private function generalDiscount(float $subtotal, ?string $type, float $value): float
    {
        if ($type === null) {
            return 0.0;
        }

        if ($value <= 0) {
            throw ValidationException::withMessages(['discountValue' => 'Discount value must be greater than zero.']);
        }

        if ($type === Sale::DISCOUNT_FIXED) {
            if ($value > $subtotal) {
                throw ValidationException::withMessages(['discountValue' => 'Fixed discount cannot exceed the sale subtotal.']);
            }

            return round($value, 2);
        }

        if ($type === Sale::DISCOUNT_PERCENTAGE) {
            if ($value > 100) {
                throw ValidationException::withMessages(['discountValue' => 'Percentage discount cannot exceed 100%.']);
            }

            return round($subtotal * ($value / 100), 2);
        }

        throw ValidationException::withMessages(['discountType' => 'Invalid discount type.']);
    }
}
