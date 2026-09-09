<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_sniperpos_uses_philippine_local_timezone_by_default(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
    }

    public function test_admin_pos_surfaces_product_creation_discount_and_payment_modes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/pos')
            ->assertOk()
            ->assertSee('/products?create=1', false)
            ->assertSee('Discount')
            ->assertSee('Mode of Payment')
            ->assertSee('Cash')
            ->assertSee('GCash')
            ->assertSee('Card')
            ->assertSee('Other')
            ->assertSee('Asia/Manila');
    }

    public function test_dashboard_quick_actions_are_rendered_with_icon_markup(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Quick Actions')
            ->assertSee('Inventory')
            ->assertSee('Sales')
            ->assertSee('Reports')
            ->assertSee('Products')
            ->assertSee('<svg', false)
            ->assertSee('Asia/Manila');
    }

    public function test_reports_render_daily_and_monthly_sales_graphs(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/reports')
            ->assertOk()
            ->assertSee('Daily Sales Graph')
            ->assertSee('Sales by Hour')
            ->assertSee('Monthly Sales Graph')
            ->assertSee('Sales by Day')
            ->assertSee('Asia/Manila');
    }
}
