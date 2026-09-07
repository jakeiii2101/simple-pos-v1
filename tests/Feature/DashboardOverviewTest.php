<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\DashboardOverview;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_cashier_can_access_dashboard(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->create();

        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($cashier)->get('/dashboard')->assertOk();
    }

    public function test_dashboard_shows_live_sales_inventory_and_recent_payment_data(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::query()->create([
            'name' => 'Dashboard Category',
            'description' => null,
            'status' => Category::STATUS_ACTIVE,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'sku' => 'DASH-001',
            'barcode' => null,
            'name' => 'Dashboard Product',
            'cost_price' => 50,
            'selling_price' => 100,
            'stock_quantity' => 1,
            'low_stock_level' => 2,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $sale = Sale::query()->create([
            'sale_number' => 'POS-DASH-001',
            'user_id' => $admin->id,
            'subtotal' => 200,
            'discount_type' => Sale::DISCOUNT_FIXED,
            'discount_value' => 20,
            'discount_amount' => 20,
            'total' => 180,
            'cash_received' => 0,
            'change_due' => 0,
            'status' => Sale::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'unit_price' => 100,
            'quantity' => 2,
            'line_total' => 200,
        ]);

        Payment::query()->create([
            'sale_id' => $sale->id,
            'method' => Payment::METHOD_GCASH,
            'amount' => 180,
            'amount_tendered' => 0,
            'change_due' => 0,
            'reference' => 'GCASH-DASH-001',
        ]);

        Livewire::actingAs($admin)
            ->test(DashboardOverview::class)
            ->assertSee('180.00')
            ->assertSee('200.00')
            ->assertSee('20.00')
            ->assertSee('POS-DASH-001')
            ->assertSee('GCash')
            ->assertSee('Dashboard Product')
            ->assertSee('2');
    }
}
