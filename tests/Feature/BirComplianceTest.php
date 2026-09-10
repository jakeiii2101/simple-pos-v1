<?php

namespace Tests\Feature;

use App\Livewire\Settings\BirSettings;
use App\Models\BirSetting;
use App\Models\InvoiceSequence;
use App\Models\User;
use App\Support\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class BirComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_and_activate_bir_settings(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(BirSettings::class)
            ->set('registeredName', 'Sniper Retail Corporation')
            ->set('tradeName', 'Sniper Mart')
            ->set('tin', '123-456-789-00000')
            ->set('branchCode', '00001')
            ->set('registeredAddress', 'General Santos City')
            ->set('rdoCode', '110')
            ->set('taxType', BirSetting::TAX_TYPE_VAT)
            ->set('vatRate', '12')
            ->set('invoicePrefix', 'SI-GSC-')
            ->set('startingNumber', '1001')
            ->set('endingNumber', '9999')
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bir_settings', [
            'registered_name' => 'Sniper Retail Corporation',
            'trade_name' => 'Sniper Mart',
            'branch_code' => '00001',
            'tax_type' => BirSetting::TAX_TYPE_VAT,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('invoice_sequences', [
            'branch_code' => '00001',
            'prefix' => 'SI-GSC-',
            'current_number' => 1000,
            'starting_number' => 1001,
            'ending_number' => 9999,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'bir.settings.updated',
        ]);
    }

    public function test_cashier_cannot_access_bir_settings(): void
    {
        $cashier = User::factory()->create();

        $this->actingAs($cashier)
            ->get(route('settings.bir', [], false))
            ->assertForbidden();
    }

    public function test_invoice_numbers_are_sequential_and_never_reused(): void
    {
        $this->configureBirInvoicing();
        $service = app(InvoiceNumberService::class);

        $first = DB::transaction(fn (): array => $service->next());
        $second = DB::transaction(fn (): array => $service->next());

        $this->assertSame('SI-GSC-000000000001', $first['invoice_number']);
        $this->assertSame('SI-GSC-000000000002', $second['invoice_number']);
        $this->assertSame(2, InvoiceSequence::query()->value('current_number'));
    }

    public function test_invoice_generation_stops_when_bir_settings_are_inactive(): void
    {
        $this->expectException(ValidationException::class);

        DB::transaction(fn (): array => app(InvoiceNumberService::class)->next());
    }

    private function configureBirInvoicing(): void
    {
        BirSetting::query()->create([
            'registered_name' => 'Sniper Retail Corporation',
            'tin' => '123-456-789-00000',
            'branch_code' => '00001',
            'registered_address' => 'General Santos City',
            'tax_type' => BirSetting::TAX_TYPE_VAT,
            'vat_rate' => 12,
            'is_active' => true,
        ]);

        InvoiceSequence::query()->create([
            'document_type' => InvoiceSequence::TYPE_SALES_INVOICE,
            'branch_code' => '00001',
            'prefix' => 'SI-GSC-',
            'current_number' => 0,
            'starting_number' => 1,
            'is_active' => true,
        ]);
    }
}
