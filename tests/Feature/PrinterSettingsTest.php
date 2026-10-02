<?php

namespace Tests\Feature;

use App\Livewire\Settings\PrinterSettings;
use App\Models\PrinterSetting;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PrinterSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_receipt_printer_settings(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(PrinterSettings::class)
            ->set('paperWidthMm', 58)
            ->set('contentPaddingMm', 2)
            ->set('fontSizePx', 10)
            ->set('showLogo', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('printer_settings', [
            'paper_width_mm' => 58,
            'content_padding_mm' => 2,
            'font_size_px' => 10,
            'show_logo' => 0,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'printer.settings.updated',
        ]);
    }

    public function test_printer_settings_validate_safe_receipt_dimensions(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(PrinterSettings::class)
            ->set('paperWidthMm', 30)
            ->set('contentPaddingMm', 20)
            ->set('fontSizePx', 30)
            ->call('save')
            ->assertHasErrors([
                'paperWidthMm',
                'contentPaddingMm',
                'fontSizePx',
            ]);
    }

    public function test_cashier_cannot_access_printer_settings(): void
    {
        $cashier = User::factory()->create();

        $this->actingAs($cashier)
            ->get('/settings/printer')
            ->assertForbidden();
    }

    public function test_sales_invoice_uses_saved_printer_size_and_logo_preference(): void
    {
        $admin = User::factory()->admin()->create();

        PrinterSetting::query()->create([
            'paper_width_mm' => 58,
            'content_padding_mm' => 2,
            'font_size_px' => 10,
            'show_logo' => false,
        ]);

        $sale = Sale::query()->create([
            'sale_number' => 'SI-PRINTER-001',
            'invoice_number' => 'SI-PRINTER-001',
            'user_id' => $admin->id,
            'subtotal' => 100,
            'discount_amount' => 0,
            'total' => 100,
            'cash_received' => 100,
            'change_due' => 0,
            'status' => Sale::STATUS_COMPLETED,
            'completed_at' => now(),
            'tax_type' => 'non_vat',
            'non_vat_sales' => 100,
            'seller_snapshot' => [
                'registered_name' => 'Sniper Retail Corporation',
                'trade_name' => 'Sniper Mart',
                'tin' => '123-456-789-00000',
                'branch_code' => '00000',
                'registered_address' => 'General Santos City',
            ],
        ]);

        $sale->items()->create([
            'product_id' => null,
            'product_name' => 'Printer Test Product',
            'sku' => 'PRINT-001',
            'unit_price' => 100,
            'quantity' => 1,
            'line_total' => 100,
        ]);

        $this->actingAs($admin)
            ->get(route('sales.invoice', ['sale' => $sale->id], false))
            ->assertOk()
            ->assertSee('--receipt-width: 58mm;', false)
            ->assertSee('--receipt-padding: 2mm;', false)
            ->assertSee('--receipt-font-size: 10px;', false)
            ->assertSee('Printer Settings')
            ->assertDontSee('simple-pos-icon.svg');
    }

    public function test_default_receipt_width_is_80_mm_when_no_setting_has_been_saved(): void
    {
        $setting = PrinterSetting::current();

        $this->assertFalse($setting->exists);
        $this->assertSame(80, $setting->paper_width_mm);
        $this->assertSame(4, $setting->content_padding_mm);
        $this->assertSame(12, $setting->font_size_px);
        $this->assertTrue($setting->show_logo);
    }
}
