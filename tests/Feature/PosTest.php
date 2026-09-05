<?php

namespace Tests\Feature;

use App\Livewire\Pos\SaleTerminal;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_cashier_can_access_pos(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->create();

        $this->actingAs($admin)->get('/pos')->assertOk();
        $this->actingAs($cashier)->get('/pos')->assertOk();
    }

    public function test_completed_sale_saves_items_and_deducts_stock(): void
    {
        $cashier = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Health Care',
            'description' => null,
            'status' => Category::STATUS_ACTIVE,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'sku' => 'VICKS-001',
            'barcode' => '4987176219619',
            'name' => 'Vicks VaporRub Extra Strong',
            'cost_price' => 100,
            'selling_price' => 120,
            'stock_quantity' => 10,
            'low_stock_level' => 2,
            'status' => Product::STATUS_ACTIVE,
        ]);

        Livewire::actingAs($cashier)
            ->test(SaleTerminal::class)
            ->call('addProduct', $product->id)
            ->call('increase', $product->id)
            ->set('cashReceived', '300.00')
            ->call('completeSale')
            ->assertHasNoErrors()
            ->assertSet('cart', []);

        $sale = Sale::query()->firstOrFail();

        $this->assertSame('240.00', $sale->total);
        $this->assertSame('60.00', $sale->change_due);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->assertSame(8, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'user_id' => $cashier->id,
            'type' => StockMovement::TYPE_SALE,
            'quantity' => -2,
            'stock_before' => 10,
            'stock_after' => 8,
            'reference' => $sale->sale_number,
        ]);
    }

    public function test_sale_cannot_complete_when_stock_changed_below_cart_quantity(): void
    {
        $cashier = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Beverages',
            'description' => null,
            'status' => Category::STATUS_ACTIVE,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'sku' => 'DRINK-001',
            'barcode' => null,
            'name' => 'Sample Drink',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock_quantity' => 2,
            'low_stock_level' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $component = Livewire::actingAs($cashier)
            ->test(SaleTerminal::class)
            ->call('addProduct', $product->id)
            ->call('increase', $product->id)
            ->set('cashReceived', '100.00');

        $product->update(['stock_quantity' => 1]);

        $component->call('completeSale')->assertHasErrors('cart');

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(1, $product->fresh()->stock_quantity);
    }
}
