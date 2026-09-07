<?php

namespace Tests\Feature;

use App\Livewire\Sales\SalesHistory;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class SalesHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_access_sales_history(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/sales')
            ->assertOk();
    }

    public function test_cashier_cannot_access_sales_history(): void
    {
        $cashier = User::factory()->create();

        $this->actingAs($cashier)
            ->get('/sales')
            ->assertForbidden();
    }

    public function test_sales_history_shows_discount_aware_summary_and_can_filter_by_sale_number(): void
    {
        $admin = User::factory()->admin()->create();

        $sale = $this->createSale($admin, 'POS-TEST-001', now(), 240, 40, 200);
        $sale->items()->create([
            'product_id' => null,
            'product_name' => 'Test Product',
            'sku' => 'TEST-001',
            'unit_price' => 120,
            'quantity' => 2,
            'line_total' => 240,
        ]);

        Livewire::actingAs($admin)
            ->test(SalesHistory::class)
            ->assertSee('POS-TEST-001')
            ->assertSee('240.00')
            ->assertSee('40.00')
            ->assertSee('200.00')
            ->set('search', 'POS-TEST-001')
            ->assertSee('POS-TEST-001')
            ->set('search', 'DOES-NOT-EXIST')
            ->assertDontSee('POS-TEST-001');
    }

    public function test_sales_history_preset_filters_use_server_generated_date_ranges(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-09 12:00:00'));
        $admin = User::factory()->admin()->create();

        $this->createSale($admin, 'POS-TODAY', now()->copy(), 100, 0, 100);
        $this->createSale($admin, 'POS-YESTERDAY', now()->copy()->subDay(), 100, 0, 100);
        $this->createSale($admin, 'POS-LAST-MONTH', now()->copy()->subMonth(), 100, 0, 100);

        $component = Livewire::actingAs($admin)->test(SalesHistory::class);

        $component
            ->call('setPreset', 'today')
            ->assertSet('dateFrom', '2026-09-09')
            ->assertSet('dateTo', '2026-09-09')
            ->assertSee('POS-TODAY')
            ->assertDontSee('POS-YESTERDAY');

        $component
            ->call('setPreset', 'this_week')
            ->assertSee('POS-TODAY')
            ->assertSee('POS-YESTERDAY')
            ->assertDontSee('POS-LAST-MONTH');

        $component
            ->call('setPreset', 'all')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '')
            ->assertSee('POS-LAST-MONTH');
    }

    public function test_custom_sales_dates_are_validated_server_side(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(SalesHistory::class)
            ->set('dateFrom', 'not-a-date')
            ->assertHasErrors('dateFrom')
            ->set('dateFrom', '2026-09-10')
            ->set('dateTo', '2026-09-09')
            ->assertHasErrors('dateTo');
    }

    private function createSale(
        User $user,
        string $number,
        Carbon $completedAt,
        float $subtotal,
        float $discount,
        float $total,
    ): Sale {
        return Sale::query()->create([
            'sale_number' => $number,
            'user_id' => $user->id,
            'subtotal' => $subtotal,
            'discount_type' => $discount > 0 ? Sale::DISCOUNT_FIXED : null,
            'discount_value' => $discount,
            'discount_amount' => $discount,
            'total' => $total,
            'cash_received' => $total,
            'change_due' => 0,
            'status' => Sale::STATUS_COMPLETED,
            'completed_at' => $completedAt,
        ]);
    }
}
