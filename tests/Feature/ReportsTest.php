<?php

namespace Tests\Feature;

use App\Livewire\Reports\ReportsDashboard;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
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
}
