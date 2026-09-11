<?php

namespace Tests\Feature;

use App\Livewire\Pos\SaleTerminal;
use App\Livewire\Reports\DailyReadings;
use App\Models\Category;
use App\Models\DailyClosing;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleAdjustment;
use App\Models\SaleRefund;
use App\Models\User;
use App\Support\DailyReadingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class DailyReadingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_x_reading_with_invoice_tax_payment_and_reversal_totals(): void
    {
        $admin = User::factory()->admin()->create();
        $active = $this->createSale($admin, 'SI-0001', 112);
        $voided = $this->createSale($admin, 'SI-0002', 224);
        SaleAdjustment::query()->create([
            'sale_id' => $voided->id,
            'authorized_by' => $admin->id,
            'type' => SaleAdjustment::TYPE_VOID,
            'amount' => 224,
            'reason' => 'Duplicate transaction in reading test',
            'inventory_restocked' => true,
            'processed_at' => now(),
        ]);

        Livewire::actingAs($admin)->test(DailyReadings::class)
            ->assertViewHas('snapshot', function (array $snapshot): bool {
                return $snapshot['invoice_range']['first'] === 'SI-0001'
                    && $snapshot['invoice_range']['last'] === 'SI-0002'
                    && $snapshot['invoice_range']['issued_count'] === 2
                    && $snapshot['sales']['transaction_count'] === 1
                    && $snapshot['sales']['net_sales'] === 112.0
                    && $snapshot['tax']['vatable_sales'] === 100.0
                    && $snapshot['tax']['vat_amount'] === 12.0
                    && $snapshot['payments'][Payment::METHOD_CASH]['amount'] === 112.0
                    && $snapshot['reversals']['void_amount'] === 224.0;
            })
            ->assertSee('SI-0001')
            ->assertSee('SI-0002');

        $this->assertNotNull($active);
    }

    public function test_admin_can_create_only_one_immutable_z_reading_per_date(): void
    {
        $admin = User::factory()->admin()->create();
        $this->createSale($admin, 'SI-CLOSE-001', 112);

        Livewire::actingAs($admin)->test(DailyReadings::class)
            ->set('notes', 'End of day verified by administrator')
            ->set('authorizationPassword', 'password')
            ->set('confirmed', true)
            ->call('createZReading')
            ->assertHasNoErrors();

        $closing = DailyClosing::query()->firstOrFail();
        $this->assertSame('Z-'.now()->format('Ymd'), $closing->reading_number);
        $this->assertEquals(112.0, $closing->snapshot['sales']['net_sales']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'daily_closing.created', 'auditable_id' => $closing->id]);

        $this->expectException(LogicException::class);
        $closing->update(['notes' => 'Attempted change']);
    }

    public function test_duplicate_z_reading_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $service = app(DailyReadingService::class);
        $this->actingAs($admin);
        $service->close(now(), $admin, null);

        Livewire::actingAs($admin)->test(DailyReadings::class)
            ->set('authorizationPassword', 'password')
            ->set('confirmed', true)
            ->call('createZReading')
            ->assertHasErrors('closing');

        $this->assertDatabaseCount('daily_closings', 1);
    }

    public function test_z_reading_requires_admin_password_and_confirmation(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(DailyReadings::class)
            ->set('authorizationPassword', 'wrong-password')
            ->call('createZReading')
            ->assertHasErrors(['authorizationPassword', 'confirmed']);

        $this->assertDatabaseCount('daily_closings', 0);
    }

    public function test_cashier_cannot_access_daily_readings(): void
    {
        $cashier = User::factory()->create();

        $this->actingAs($cashier)->get('/daily-readings')->assertForbidden();
    }

    public function test_pos_rejects_new_sale_after_today_z_reading(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->create();
        $product = $this->createProduct();
        $this->actingAs($admin);
        app(DailyReadingService::class)->close(now(), $admin, 'Closed for POS lock test');

        Livewire::actingAs($cashier)->test(SaleTerminal::class)
            ->call('addProduct', $product->id)
            ->set('cashReceived', '100')
            ->call('completeSale')
            ->assertHasErrors('cart');

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(5, $product->fresh()->stock_quantity);
    }

    public function test_x_and_z_readings_reconcile_partial_refunds(): void
    {
        $admin = User::factory()->admin()->create();
        $sale = $this->createSale($admin, 'SI-PARTIAL-Z', 336);
        SaleRefund::query()->create([
            'refund_number' => 'RF-00000000000000000000000002',
            'sale_id' => $sale->id,
            'authorized_by' => $admin->id,
            'gross_amount' => 112,
            'vatable_sales' => 100,
            'vat_amount' => 12,
            'refund_amount' => 112,
            'reason' => 'Partial return included in reading',
            'inventory_restocked' => false,
            'processed_at' => now(),
        ]);

        $snapshot = app(DailyReadingService::class)->snapshot(now());

        $this->assertSame(224.0, $snapshot['sales']['net_sales']);
        $this->assertSame(200.0, $snapshot['tax']['vatable_sales']);
        $this->assertSame(24.0, $snapshot['tax']['vat_amount']);
        $this->assertSame(1, $snapshot['reversals']['partial_refund_count']);
        $this->assertSame(112.0, $snapshot['reversals']['partial_refund_amount']);

        $closing = app(DailyReadingService::class)->close(now(), $admin, 'Partial refund reconciled');
        $this->assertSame(224.0, $closing->snapshot['sales']['net_sales']);
    }

    private function createSale(User $user, string $invoice, float $total): Sale
    {
        $vatable = round($total / 1.12, 2);
        $sale = Sale::query()->create([
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
        ]);
        $sale->payment()->create([
            'method' => Payment::METHOD_CASH,
            'amount' => $total,
            'amount_tendered' => $total,
            'change_due' => 0,
        ]);

        return $sale;
    }

    private function createProduct(): Product
    {
        $category = Category::query()->create(['name' => 'Closing Test', 'status' => Category::STATUS_ACTIVE]);

        return Product::query()->create([
            'category_id' => $category->id,
            'sku' => 'CLOSE-001',
            'name' => 'Closing Test Product',
            'cost_price' => 50,
            'selling_price' => 100,
            'stock_quantity' => 5,
            'low_stock_level' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);
    }
}
