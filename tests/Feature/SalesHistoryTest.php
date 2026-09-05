<?php

namespace Tests\Feature;

use App\Livewire\Sales\SalesHistory;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesHistoryTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_sales_history_shows_summary_and_can_filter_by_sale_number(): void
    {
        $admin = User::factory()->admin()->create();

        $sale = Sale::query()->create([
            'sale_number' => 'POS-TEST-001',
            'user_id' => $admin->id,
            'subtotal' => 240,
            'total' => 240,
            'cash_received' => 300,
            'change_due' => 60,
            'status' => Sale::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

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
            ->set('search', 'POS-TEST-001')
            ->assertSee('POS-TEST-001')
            ->set('search', 'DOES-NOT-EXIST')
            ->assertDontSee('POS-TEST-001');
    }
}
