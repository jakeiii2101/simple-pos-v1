<?php

namespace Tests\Feature;

use App\Livewire\Pos\SaleTerminal;
use App\Models\Category;
use App\Models\Payment;
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

    public function test_completed_cash_sale_saves_payment_items_deducts_stock_and_audits(): void
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
        $this->assertDatabaseHas('payments', [
            'sale_id' => $sale->id,
            'method' => Payment::METHOD_CASH,
            'amount' => 240,
            'amount_tendered' => 300,
            'change_due' => 60,
            'reference' => null,
        ]);
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
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $cashier->id,
            'action' => 'sale.completed',
            'auditable_type' => Sale::class,
            'auditable_id' => $sale->id,
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

    public function test_gcash_checkout_creates_payment_reference_without_cash_change(): void
    {
        $cashier = User::factory()->create();
        $product = $this->createProduct('GCASH-001', 'GCash Item', 150, 5);

        Livewire::actingAs($cashier)
            ->test(SaleTerminal::class)
            ->call('addProduct', $product->id)
            ->set('paymentMethod', Payment::METHOD_GCASH)
            ->set('paymentReference', 'GCASH-REF-12345')
            ->call('completeSale')
            ->assertHasNoErrors();

        $sale = Sale::query()->firstOrFail();

        $this->assertSame('0.00', $sale->cash_received);
        $this->assertSame('0.00', $sale->change_due);
        $this->assertDatabaseHas('payments', [
            'sale_id' => $sale->id,
            'method' => Payment::METHOD_GCASH,
            'amount' => 150,
            'amount_tendered' => 150,
            'change_due' => 0,
            'reference' => 'GCASH-REF-12345',
        ]);
    }

    public function test_card_and_other_payment_methods_are_supported(): void
    {
        foreach ([Payment::METHOD_CARD, Payment::METHOD_OTHER] as $index => $method) {
            $cashier = User::factory()->create();
            $product = $this->createProduct(
                'NONCASH-'.($index + 1),
                'Non Cash Item '.($index + 1),
                75,
                5,
            );
            $reference = strtoupper($method).'-REF-'.($index + 1);

            Livewire::actingAs($cashier)
                ->test(SaleTerminal::class)
                ->call('addProduct', $product->id)
                ->set('paymentMethod', $method)
                ->set('paymentReference', $reference)
                ->call('completeSale')
                ->assertHasNoErrors();

            $this->assertDatabaseHas('payments', [
                'method' => $method,
                'amount' => 75,
                'reference' => $reference,
            ]);
        }
    }

    public function test_non_cash_payment_requires_reference(): void
    {
        $cashier = User::factory()->create();
        $product = $this->createProduct('REF-001', 'Reference Required Item', 100, 5);

        Livewire::actingAs($cashier)
            ->test(SaleTerminal::class)
            ->call('addProduct', $product->id)
            ->set('paymentMethod', Payment::METHOD_GCASH)
            ->set('paymentReference', '')
            ->call('completeSale')
            ->assertHasErrors('paymentReference');

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('payments', 0);
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
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('audit_logs', 0);
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
