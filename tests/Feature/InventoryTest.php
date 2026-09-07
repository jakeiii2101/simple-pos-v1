<?php

namespace Tests\Feature;

use App\Livewire\Inventory\InventoryList;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_inventory_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/inventory')
            ->assertOk();
    }

    public function test_cashier_cannot_access_inventory_page(): void
    {
        $cashier = User::factory()->create();

        $this->actingAs($cashier)
            ->get('/inventory')
            ->assertForbidden();
    }

    public function test_stock_in_updates_product_creates_movement_and_audit_log(): void
    {
        $admin = User::factory()->admin()->create();
        $product = $this->createProduct(stock: 10);

        $this->actingAs($admin);

        Livewire::test(InventoryList::class)
            ->set('productId', $product->id)
            ->set('type', StockMovement::TYPE_STOCK_IN)
            ->set('quantity', 5)
            ->set('reason', 'Delivery received')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(15, $product->fresh()->stock_quantity);
        $movement = StockMovement::query()->firstOrFail();

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'type' => StockMovement::TYPE_STOCK_IN,
            'quantity' => 5,
            'stock_before' => 10,
            'stock_after' => 15,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'inventory.movement',
            'auditable_type' => StockMovement::class,
            'auditable_id' => $movement->id,
        ]);
    }

    public function test_stock_out_cannot_make_stock_negative(): void
    {
        $admin = User::factory()->admin()->create();
        $product = $this->createProduct(stock: 3);

        $this->actingAs($admin);

        Livewire::test(InventoryList::class)
            ->set('productId', $product->id)
            ->set('type', StockMovement::TYPE_STOCK_OUT)
            ->set('quantity', 5)
            ->set('reason', 'Damaged stock')
            ->call('save')
            ->assertHasErrors(['quantity']);

        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_adjustment_can_increase_or_decrease_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $product = $this->createProduct(stock: 10);

        $this->actingAs($admin);

        Livewire::test(InventoryList::class)
            ->set('productId', $product->id)
            ->set('type', StockMovement::TYPE_ADJUSTMENT)
            ->set('quantity', -2)
            ->set('reason', 'Physical count correction')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(8, $product->fresh()->stock_quantity);
    }

    private function createProduct(int $stock): Product
    {
        $category = Category::query()->create([
            'name' => 'Test Category',
            'description' => null,
            'status' => Category::STATUS_ACTIVE,
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'sku' => 'TEST-001',
            'barcode' => null,
            'name' => 'Test Product',
            'cost_price' => 10,
            'selling_price' => 15,
            'stock_quantity' => $stock,
            'low_stock_level' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);
    }
}
