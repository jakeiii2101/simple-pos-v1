<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_audit_log_and_cashier_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->create();

        $this->actingAs($admin);
        Audit::record('security.test', $admin, 'Security audit test event.');

        $this->get('/audit-logs')
            ->assertOk()
            ->assertSee('Security audit test event.')
            ->assertSee('security.test');

        $this->actingAs($cashier)
            ->get('/audit-logs')
            ->assertForbidden();
    }

    public function test_audit_log_cannot_be_updated(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $log = Audit::record('security.test', $admin, 'Immutable audit record.');

        $this->expectException(LogicException::class);

        $log->update(['description' => 'Changed']);
    }

    public function test_audit_log_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $log = Audit::record('security.test', $admin, 'Immutable audit record.');

        $this->expectException(LogicException::class);

        $log->delete();
    }

    public function test_audit_metadata_is_cast_to_array(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $log = Audit::record('security.test', $admin, 'Metadata audit record.', [
            'safe_value' => 'recorded',
        ]);

        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertSame(['safe_value' => 'recorded'], $log->metadata);
    }
}
