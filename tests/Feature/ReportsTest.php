<?php

namespace Tests\Feature;

use App\Livewire\Reports\ReportsDashboard;
use App\Models\Category;
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

    public function test_reports_show_sales_top_products_and_inventory_summary(): void
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
            'total' => 240,
            'cash_received' => 300,
            'change_due' => 60,
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

        Livewire::actingAs($admin)
            ->test(ReportsDashboard::class)
            ->set('reportDate', now()->toDateString())
            ->set('reportMonth', now()->format('Y-m'))
            ->assertSee('240.00')
            ->assertSee('Vicks VaporRub Extra Strong')
            ->assertSee('3')
            ->assertSee('300.00');
    }
}
