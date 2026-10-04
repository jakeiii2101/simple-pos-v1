<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_accounts_have_isolated_catalog_users_sales_and_audit_history(): void
    {
        [$accountA, $adminA] = $this->business('alpha-store', 'Alpha Store', 'alpha@example.test');
        [$accountB, $adminB] = $this->business('beta-store', 'Beta Store', 'beta@example.test');

        Auth::login($adminA);
        $categoryA = Category::query()->create([
            'name' => 'Beverages',
            'status' => Category::STATUS_ACTIVE,
        ]);
        $productA = Product::query()->create([
            'category_id' => $categoryA->id,
            'sku' => 'SHARED-SKU',
            'barcode' => '100000000001',
            'name' => 'Alpha Drink',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock_quantity' => 5,
            'low_stock_level' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);
        $saleA = $this->sale('SI-ALPHA-001', $adminA);
        Audit::record('tenant.test.alpha', $productA, 'Alpha account audit event.');

        Auth::logout();
        Auth::login($adminB);
        $categoryB = Category::query()->create([
            'name' => 'Beverages',
            'status' => Category::STATUS_ACTIVE,
        ]);
        $productB = Product::query()->create([
            'category_id' => $categoryB->id,
            'sku' => 'SHARED-SKU',
            'barcode' => '100000000002',
            'name' => 'Beta Drink',
            'cost_price' => 11,
            'selling_price' => 21,
            'stock_quantity' => 6,
            'low_stock_level' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);
        $saleB = $this->sale('SI-BETA-001', $adminB);
        Audit::record('tenant.test.beta', $productB, 'Beta account audit event.');

        $this->assertSame($accountA->id, $categoryA->account_id);
        $this->assertSame($accountB->id, $categoryB->account_id);
        $this->assertSame($accountA->id, $productA->account_id);
        $this->assertSame($accountB->id, $productB->account_id);
        $this->assertSame($accountA->id, $saleA->account_id);
        $this->assertSame($accountB->id, $saleB->account_id);

        Auth::logout();
        Auth::login($adminA);

        $this->assertTrue(Product::query()->whereKey($productA->id)->exists());
        $this->assertFalse(Product::query()->whereKey($productB->id)->exists());
        $this->assertTrue(Category::query()->whereKey($categoryA->id)->exists());
        $this->assertFalse(Category::query()->whereKey($categoryB->id)->exists());
        $this->assertFalse(User::query()->whereKey($adminB->id)->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'tenant.test.alpha')->exists());
        $this->assertFalse(AuditLog::query()->where('action', 'tenant.test.beta')->exists());

        $this->actingAs($adminA)
            ->get('/products')
            ->assertOk()
            ->assertSee('Alpha Drink')
            ->assertDontSee('Beta Drink');

        $this->actingAs($adminA)
            ->get(route('sales.invoice', ['sale' => $saleB->id], false))
            ->assertNotFound();
    }

    private function business(string $slug, string $name, string $email): array
    {
        $account = Account::query()->create([
            'name' => $name,
            'slug' => $slug,
            'status' => Account::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);

        $admin = User::factory()->admin()->create([
            'account_id' => $account->id,
            'email' => $email,
        ]);

        $account->update(['owner_user_id' => $admin->id]);

        return [$account, $admin];
    }

    private function sale(string $number, User $user): Sale
    {
        return Sale::query()->create([
            'sale_number' => $number,
            'invoice_number' => $number,
            'user_id' => $user->id,
            'subtotal' => 100,
            'total' => 100,
            'cash_received' => 100,
            'change_due' => 0,
            'status' => Sale::STATUS_COMPLETED,
            'completed_at' => now(),
            'tax_type' => 'non_vat',
            'non_vat_sales' => 100,
        ]);
    }
}
