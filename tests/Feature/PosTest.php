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
        $product = $this->createProduct(
            sku: 'VICKS-001',
            name: 'Vicks VaporRub Extra Strong',
            price: 120,
            stock: 10,
            barcode: '4987176219619',
        );

        Livewire::actingAs($cashier)
            ->test(SaleTerminal::class)
            ->call('addProduct', $product->id)
            ->call('increase', $product->id)
            ->set('cashReceived', '300.00')
            ->call('completeSale')
            ->assertHasNoErrors()
            ->assertSet('cart', []);

        $sale = Sale::query()->firstOrFail();

        $this->assertSame('240.00', $sale->subtotal);
        $this->assertNull($sale->discount_type);
        $this->assertSame('0.00', $sale->discount_amount);
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

    public function test_fixed_discount_is_applied_and_persisted_server_side(): void
    {
        $cashier = User::factory()->create();
        $product = $this->createProduct('FIXED-001', 'Fixed Discount Item', 200, 5);

        Livewire::actingAs($cashier)
            ->test(SaleTerminal::class)
            ->call('addProduct', $product->id)
            ->set('discountType', Sale::DISCOUNT_FIXED)
            ->set('discountValue', '50.00')
            ->call('applyDiscount')
            ->assertHasNoErrors()
            ->assertSet('appliedDiscountType', Sale::DISCOUNT_FIXED)
            ->set('cashReceived', '200.00')
            ->call('completeSale')
            ->assertHasNoErrors();

        $sale = Sale::query()->firstOrFail();

        $this->assertSame('200.00', $sale->subtotal);
        $this->assertSame(Sale::DISCOUNT_FIXED, $sale->discount_type);
        $this->assertSame('50.00', $sale->discount_value);
        $this->assertSame('50.00', $sale->discount_amount);
        $this->assertSame('150.00', $sale->total);
        $this->assertSame('50.00', $sale->change_due);
    }

    public function test_percentage_discount_is_applied_and_persisted_server_side(): void
    {
        $cashier = User::factory()->create();
        $product = $this->createProduct('PERCENT-001', 'Percentage Discount Item', 200, 5);

        Livewire::actingAs($cashier)
            ->test(SaleTerminal::class)
            ->call('addProduct', $product->id)
            ->set('discountType', Sale::DISCOUNT_PERCENTAGE)
            ->set('discountValue', '25')
            ->call('applyDiscount')
            ->assertHasNoErrors()
            ->set('cashReceived', '200.00')
            ->call('completeSale')
            ->assertHasNoErrors();

        $sale = Sale::query()->firstOrFail();

        $this->assertSame('200.00', $sale->subtotal);
        $this->assertSame(Sale::DISCOUNT_PERCENTAGE, $sale->discount_type);
        $this->assertSame('25.00', $sale->discount_value);
        $this->assertSame('50.00', $sale->discount_amount);
        $this->assertSame('150.00', $sale->total);
    }

    public function test_discount_validation_rejects_values_outside_allowed_limits(): void
    {
        $cashier = User::factory()->create();
        $product = $this->createProduct('LIMIT-001', 'Discount Limit Item', 100, 5);

        Livewire::actingAs($cashier)
            ->test(SaleTerminal::class)
            ->call('addProduct', $product->id)
            ->set('discountType', Sale::DISCOUNT_FIXED)
            ->set('discountValue', '101')
            ->call('applyDiscount')
            ->assertHasErrors('discountValue')
            ->assertSet('appliedDiscountType', null)
            ->set('discountType', Sale::DISCOUNT_PERCENTAGE)
            ->set('discountValue', '101')
            ->call('applyDiscount')
            ->assertHasErrors('discountValue')
            ->assertSet('appliedDiscountType', null);
    }

    public function test_discount_can_be_cleared_before_checkout(): void
    {
        $cashier = User::factory()->create();
        $product = $this->createProduct('CLEAR-001', 'Clear Discount Item', 100, 5);

        Livewire::actingAs($cashier)
            ->test(SaleTerminal::class)
            ->call('addProduct', $product->id)
            ->set('discountType', Sale::DISCOUNT_FIXED)
            ->set('discountValue', '10')
            ->call('applyDiscount')
            ->assertSet('appliedDiscountType', Sale::DISCOUNT_FIXED)
            ->call('clearDiscount')
            ->assertSet('appliedDiscountType', null)
            ->assertSet('appliedDiscountValue', 0.0)
            ->assertSet('discountValue', '');
    }

    public function test_checkout_recalculates_authoritative_price_and_discount_inside_transaction(): void
    {
        $cashier = User::factory()->create();
        $product = $this->createProduct('PRICE-001', 'Price Change Item', 100, 5);

        $component = Livewire::actingAs($cashier)
            ->test(SaleTerminal::class)
            ->call('addProduct', $product->id)
            ->set('discountType', Sale::DISCOUNT_PERCENTAGE)
            ->set('discountValue', '10')
            ->call('applyDiscount');

        $product->update(['selling_price' => 120]);

        $component
            ->set('cashReceived', '108.00')
            ->call('completeSale')
            ->assertHasNoErrors();

        $sale = Sale::query()->firstOrFail();

        $this->assertSame('120.00', $sale->subtotal);
        $this->assertSame('12.00', $sale->discount_amount);
        $this->assertSame('108.00', $sale->total);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'unit_price' => 120,
            'line_total' => 120,
        ]);
    }

    public function test_sale_cannot_complete_when_stock_changed_below_cart_quantity(): void
    {
        $cashier = User::factory()->create();
        $product = $this->createProduct('DRINK-001', 'Sample Drink', 20, 2);

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

    private function createProduct(
        string $sku,
        string $name,
        float $price,
        int $stock,
        ?string $barcode = null,
    ): Product {
        $category = Category::query()->firstOrCreate(
            ['name' => 'POS Test Category'],
            [
                'description' => null,
                'status' => Category::STATUS_ACTIVE,
            ],
        );

        return Product::query()->create([
            'category_id' => $category->id,
            'sku' => $sku,
            'barcode' => $barcode,
            'name' => $name,
            'cost_price' => 10,
            'selling_price' => $price,
            'stock_quantity' => $stock,
            'low_stock_level' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);
    }
}
