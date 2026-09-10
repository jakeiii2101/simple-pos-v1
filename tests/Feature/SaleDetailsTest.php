<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class SaleDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_sale_details_with_discount_and_payment_information(): void
    {
        $admin = User::factory()->admin()->create();
        $sale = $this->createCompletedSale($admin, Payment::METHOD_GCASH, 'GCASH-DETAIL-001');

        $this->actingAs($admin)
            ->get(route('sales.show', ['sale' => $sale->id], false))
            ->assertOk()
            ->assertSee($sale->sale_number)
            ->assertSee('GCash')
            ->assertSee('GCASH-DETAIL-001')
            ->assertSee('Discount')
            ->assertSee('New Sale')
            ->assertSee('Protected financial record');
    }

    public function test_cashier_cannot_open_admin_sale_details_but_can_open_receipt(): void
    {
        $cashier = User::factory()->create();
        $sale = $this->createCompletedSale($cashier, Payment::METHOD_CASH);

        $this->actingAs($cashier)
            ->get(route('sales.show', ['sale' => $sale->id], false))
            ->assertForbidden();

        $this->actingAs($cashier)
            ->get(route('sales.invoice', ['sale' => $sale->id], false))
            ->assertOk()
            ->assertSee('SniperPOS')
            ->assertSee('SALES INVOICE')
            ->assertSee('TIN: 123-456-789-00000')
            ->assertSee('Cash Tendered')
            ->assertSee('New Sale');
    }

    public function test_receipt_shows_non_cash_reference_and_discount(): void
    {
        $admin = User::factory()->admin()->create();
        $sale = $this->createCompletedSale($admin, Payment::METHOD_CARD, 'CARD-AUTH-7788');

        $this->actingAs($admin)
            ->get(route('sales.invoice', ['sale' => $sale->id], false))
            ->assertOk()
            ->assertSee('Precision in Every Sale.')
            ->assertSee('Card')
            ->assertSee('CARD-AUTH-7788')
            ->assertSee('Discount')
            ->assertSee('₱90.00');
    }

    public function test_legacy_sale_without_payment_record_still_renders_as_cash(): void
    {
        $admin = User::factory()->admin()->create();
        $sale = $this->createCompletedSale($admin, null);

        $this->actingAs($admin)
            ->get(route('sales.invoice', ['sale' => $sale->id], false))
            ->assertOk()
            ->assertSee('Cash')
            ->assertSee('Cash Tendered');
    }

    public function test_completed_sale_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $sale = $this->createCompletedSale($admin, Payment::METHOD_CASH);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('cannot be deleted');

        $sale->delete();
    }

    public function test_completed_sale_financial_values_cannot_be_changed(): void
    {
        $admin = User::factory()->admin()->create();
        $sale = $this->createCompletedSale($admin, Payment::METHOD_CASH);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('immutable financial history');

        $sale->update(['total' => 1]);
    }

    private function createCompletedSale(User $cashier, ?string $method, ?string $reference = null): Sale
    {
        $saleNumber = 'SI-'.strtoupper(bin2hex(random_bytes(4)));

        $sale = Sale::query()->create([
            'sale_number' => $saleNumber,
            'invoice_number' => $saleNumber,
            'user_id' => $cashier->id,
            'subtotal' => 100,
            'discount_type' => Sale::DISCOUNT_PERCENTAGE,
            'discount_value' => 10,
            'discount_amount' => 10,
            'total' => 90,
            'cash_received' => $method === Payment::METHOD_CASH || $method === null ? 100 : 0,
            'change_due' => $method === Payment::METHOD_CASH || $method === null ? 10 : 0,
            'status' => Sale::STATUS_COMPLETED,
            'completed_at' => now(),
            'tax_type' => 'vat',
            'vatable_sales' => 80.36,
            'vat_amount' => 9.64,
            'vat_exempt_sales' => 0,
            'zero_rated_sales' => 0,
            'non_vat_sales' => 0,
            'seller_snapshot' => [
                'registered_name' => 'Sniper Retail Corporation',
                'trade_name' => 'Sniper Mart',
                'tin' => '123-456-789-00000',
                'branch_code' => '00000',
                'registered_address' => 'General Santos City',
                'tax_type' => 'vat',
                'vat_rate' => '12.00',
            ],
        ]);

        $sale->items()->create([
            'product_id' => null,
            'product_name' => 'Receipt Test Product',
            'sku' => 'RECEIPT-001',
            'unit_price' => 100,
            'quantity' => 1,
            'line_total' => 100,
        ]);

        if ($method !== null) {
            $sale->payment()->create([
                'method' => $method,
                'amount' => 90,
                'amount_tendered' => $method === Payment::METHOD_CASH ? 100 : 90,
                'change_due' => $method === Payment::METHOD_CASH ? 10 : 0,
                'reference' => $reference,
            ]);
        }

        return $sale->fresh(['items', 'payment', 'user']);
    }
}
