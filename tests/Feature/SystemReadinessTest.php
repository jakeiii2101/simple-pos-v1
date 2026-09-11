<?php

namespace Tests\Feature;

use App\Livewire\Settings\SystemReadiness;
use App\Models\AuditLog;
use App\Models\InvoiceSequence;
use App\Models\Sale;
use App\Models\User;
use App\Support\DatabaseBackupService;
use App\Support\SystemReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SystemReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_readiness_and_cashier_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->create();

        $this->actingAs($admin)->get('/settings/readiness')->assertOk();
        $this->actingAs($cashier)->get('/settings/readiness')->assertForbidden();
    }

    public function test_invoice_integrity_detects_internal_gap_and_malformed_number(): void
    {
        $admin = User::factory()->admin()->create();
        InvoiceSequence::query()->create([
            'document_type' => InvoiceSequence::TYPE_SALES_INVOICE,
            'branch_code' => '00000',
            'prefix' => 'SI-',
            'current_number' => 3,
            'starting_number' => 1,
            'is_active' => true,
        ]);
        $this->createSale($admin, 'SI-000000000001');
        $this->createSale($admin, 'SI-000000000003');
        $this->createSale($admin, 'LEGACY-ABC');

        $readiness = app(SystemReadinessService::class)->inspect();

        $this->assertSame([2], $readiness['missing_numbers']);
        $this->assertSame(['LEGACY-ABC'], $readiness['malformed_invoices']);
        $this->assertFalse($readiness['sequence_behind']);
    }

    public function test_sqlite_backup_creates_private_copy_and_checksum(): void
    {
        Storage::fake('local');
        $source = tempnam(sys_get_temp_dir(), 'sniperpos-test-');
        file_put_contents($source, 'sqlite-test-content');
        $originalConnection = config('database.default');
        config()->set('database.default', 'backup_test');
        config()->set('database.connections.backup_test', ['driver' => 'sqlite', 'database' => $source]);
        $filename = app(DatabaseBackupService::class)->create();
        config()->set('database.default', $originalConnection);

        Storage::disk('local')->assertExists('backups/'.$filename);
        Storage::disk('local')->assertExists('backups/'.$filename.'.sha256');
        $expectedHash = hash_file('sha256', Storage::disk('local')->path('backups/'.$filename));
        $this->assertStringContainsString($expectedHash, Storage::disk('local')->get('backups/'.$filename.'.sha256'));
        unlink($source);
    }

    public function test_backup_creation_requires_current_admin_password(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(SystemReadiness::class)
            ->set('authorizationPassword', 'wrong-password')
            ->call('createBackup')
            ->assertHasErrors('authorizationPassword');
    }

    public function test_backup_download_is_admin_only_and_rejects_unsafe_filename(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('backups/sniperpos-20260911-020000.sql', 'backup-content');
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->create();

        $this->actingAs($admin)->get('/compliance/backups/sniperpos-20260911-020000.sql')->assertOk()->assertDownload();
        $this->actingAs($cashier)->get('/compliance/backups/sniperpos-20260911-020000.sql')->assertForbidden();
        $this->actingAs($admin)->get('/compliance/backups/not-a-backup.sql')->assertNotFound();
    }

    public function test_admin_can_export_formula_safe_audit_csv(): void
    {
        $admin = User::factory()->admin()->create();
        AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'test.export',
            'description' => '=Unsafe audit description',
        ]);
        $range = ['from' => now()->toDateString(), 'to' => now()->toDateString()];

        $response = $this->actingAs($admin)->get(route('compliance.audit', $range));
        $response->assertOk()->assertDownload();
        $content = $response->streamedContent();
        $this->assertStringContainsString('test.export', $content);
        $this->assertStringContainsString("'=Unsafe audit description", $content);
    }

    private function createSale(User $user, string $invoice): Sale
    {
        return Sale::query()->create([
            'sale_number' => $invoice,
            'invoice_number' => $invoice,
            'user_id' => $user->id,
            'subtotal' => 100,
            'discount_amount' => 0,
            'total' => 100,
            'cash_received' => 100,
            'change_due' => 0,
            'status' => Sale::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }
}
