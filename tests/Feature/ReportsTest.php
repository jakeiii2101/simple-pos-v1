<?php

namespace Tests\Feature;

use App\Livewire\Reports\ReportsDashboard;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_reports_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/reports')
            ->assertOk();
    }

    public function test_cashier_cannot_access_reports_page(): void
    {
        $cashier = User::factory()->create();

        $this->actingAs($cashier)
            ->get('/reports')
            ->assertForbidden();
    }

    public function test_reports_show_gross_discounts_net_payments_top_products_and_inventory(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::query()->create([
            'name' => 'Health Care',
            'description' => null,
            'status' => Category::STATUS_ACTIVE,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'sku' => 'VICKS-001',
            'barcode' => null,
            'name' => 'Vicks VaporRub Extra Strong',
            'cost_price' => 100,
            'selling_price' => 120,
            'stock_quantity' => 3,
            'low_stock_level' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $sale = Sale::query()->create([
            'sale_number' => 'POS-REPORT-001',
            'user_id' => $admin->id,
            'subtotal' => 240,
            'discount_type' => Sale::DISCOUNT_FIXED,
            'discount_value' => 40,
            'discount_amount' => 40,
            'total' => 200,
            'cash_received' => 0,
            'change_due' => 0,
            'status' => Sale::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'unit_price' => 120,
            'quantity' => 2,
            'line_total' => 240,
        ]);

        Payment::query()->create([
            'sale_id' => $sale->id,
            'method' => Payment::METHOD_GCASH,
            'amount' => 200,
            'amount_tendered' => 200,
            'change_due' => 0,
            'reference' => 'GCASH-REPORT-001',
        ]);

        Livewire::actingAs($admin)
            ->test(ReportsDashboard::class)
            ->set('reportDate', now()->toDateString())
            ->set('reportMonth', now()->format('Y-m'))
            ->assertSee('240.00')
            ->assertSee('40.00')
            ->assertSee('200.00')
            ->assertSee('GCash')
            ->assertSee('Vicks VaporRub Extra Strong')
            ->assertSee('3')
            ->assertSee('300.00');
    }

    public function test_bir_summary_excludes_reversed_sales_and_lists_the_reversal(): void
    {
        $admin = User::factory()->admin()->create();
        $active = $this->createBirSale($admin, 'SI-ACTIVE', 112);
        $reversed = $this->createBirSale($admin, 'SI-VOID', 224);
        SaleAdjustment::query()->create([
            'sale_id' => $reversed->id,
            'authorized_by' => $admin->id,
            'type' => SaleAdjustment::TYPE_VOID,
            'amount' => 224,
            'reason' => 'Duplicate transaction for report test',
            'inventory_restocked' => true,
            'processed_at' => now(),
        ]);

        Livewire::actingAs($admin)->test(ReportsDashboard::class)
            ->set('reportMonth', now()->format('Y-m'))
            ->assertViewHas('vatableSales', 100.0)
            ->assertViewHas('vatAmount', 12.0)
            ->assertViewHas('reversalCount', 1)
            ->assertViewHas('reversalAmount', 224.0)
            ->assertSee('SI-VOID')
            ->assertSee('Duplicate transaction for report test');
    }

    public function test_admin_can_download_bir_sales_and_reversal_csv_exports(): void
    {
        $admin = User::factory()->admin()->create();
        $sale = $this->createBirSale($admin, 'SI-CSV-001', 112, '=Unsafe Buyer');
        SaleAdjustment::query()->create([
            'sale_id' => $sale->id,
            'authorized_by' => $admin->id,
            'type' => SaleAdjustment::TYPE_REFUND,
            'amount' => 112,
            'reason' => 'Customer requested full return',
            'inventory_restocked' => false,
            'processed_at' => now(),
        ]);
        $range = ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()];

        $salesResponse = $this->actingAs($admin)->get(route('reports.export.sales', $range));
        $salesResponse->assertOk()->assertDownload();
        $salesCsv = $salesResponse->streamedContent();
        $this->assertStringContainsString('SI-CSV-001', $salesCsv);
        $this->assertStringContainsString("'=Unsafe Buyer", $salesCsv);
        $this->assertStringContainsString('REFUND', $salesCsv);

        $reversalResponse = $this->actingAs($admin)->get(route('reports.export.reversals', $range));
        $reversalResponse->assertOk()->assertDownload();
        $this->assertStringContainsString('Customer requested full return', $reversalResponse->streamedContent());
        $this->assertStringContainsString('NO', $reversalResponse->streamedContent());
    }

    public function test_cashier_cannot_download_bir_exports(): void
    {
        $cashier = User::factory()->create();
        $range = ['from' => now()->toDateString(), 'to' => now()->toDateString()];

        $this->actingAs($cashier)->get(route('reports.export.sales', $range))->assertForbidden();
        $this->actingAs($cashier)->get(route('reports.export.reversals', $range))->assertForbidden();
    }

    private function createBirSale(User $user, string $invoice, float $total, ?string $buyerName = null): Sale
    {
        $vatable = round($total / 1.12, 2);

        return Sale::query()->create([
            'sale_number' => $invoice,
            'invoice_number' => $invoice,
            'user_id' => $user->id,
            'subtotal' => $total,
            'discount_amount' => 0,
            'total' => $total,
            'cash_received' => $total,
            'change_due' => 0,
            'status' => Sale::STATUS_COMPLETED,
            'completed_at' => now(),
            'tax_type' => 'vat',
            'vatable_sales' => $vatable,
            'vat_amount' => round($total - $vatable, 2),
            'buyer_name' => $buyerName,
        ]);
    }
}
