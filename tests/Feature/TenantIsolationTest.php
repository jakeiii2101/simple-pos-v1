<?php

namespace Tests\Feature;

use App\Livewire\Users\UserManagement;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_businesses_can_use_same_catalog_identifiers_without_seeing_each_other_data(): void
    {
        [$businessA, $adminA] = $this->businessAdmin('Alpha Store', 'alpha@example.com');
        [$businessB, $adminB] = $this->businessAdmin('Beta Store', 'beta@example.com');

        $this->actingAs($adminA);
        $categoryA = Category::query()->create([
            'name' => 'General',
            'status' => Category::STATUS_ACTIVE,
        ]);
        Product::query()->create([
            'category_id' => $categoryA->id,
            'sku' => 'SHARED-001',
            'barcode' => '480000000001',
            'name' => 'Alpha Product',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock_quantity' => 5,
            'low_stock_level' => 1,
            'status' => Product::STATUS_ACTIVE,
            'tax_type' => Product::TAX_VATABLE,
        ]);

        $this->actingAs($adminB);
        $categoryB = Category::query()->create([
            'name' => 'General',
            'status' => Category::STATUS_ACTIVE,
        ]);
        Product::query()->create([
            'category_id' => $categoryB->id,
            'sku' => 'SHARED-001',
            'barcode' => '480000000001',
            'name' => 'Beta Product',
            'cost_price' => 11,
            'selling_price' => 21,
            'stock_quantity' => 6,
            'low_stock_level' => 1,
            'status' => Product::STATUS_ACTIVE,
            'tax_type' => Product::TAX_VATABLE,
        ]);

        $visibleToBeta = Product::query()->get();

        $this->assertCount(1, $visibleToBeta);
        $this->assertSame('Beta Product', $visibleToBeta->first()->name);
        $this->assertSame($businessB->id, $visibleToBeta->first()->business_id);

        $allProducts = Product::withoutGlobalScopes()->orderBy('business_id')->get();
        $this->assertCount(2, $allProducts);
        $this->assertSame([$businessA->id, $businessB->id], $allProducts->pluck('business_id')->sort()->values()->all());
    }

    public function test_tenant_admin_cannot_open_another_business_sale_or_manage_its_users(): void
    {
        [, $adminA] = $this->businessAdmin('Alpha Store', 'alpha-admin@example.com');
        [, $adminB] = $this->businessAdmin('Beta Store', 'beta-admin@example.com');

        $this->actingAs($adminA);
        $sale = Sale::query()->create([
            'sale_number' => 'SI-000000000001',
            'invoice_number' => 'SI-000000000001',
            'user_id' => $adminA->id,
            'subtotal' => 100,
            'discount_amount' => 0,
            'total' => 100,
            'cash_received' => 100,
            'change_due' => 0,
            'status' => Sale::STATUS_COMPLETED,
            'completed_at' => now(),
            'tax_type' => 'non_vat',
            'non_vat_sales' => 100,
        ]);

        $this->actingAs($adminB)
            ->get(route('sales.invoice', ['sale' => $sale->id], false))
            ->assertNotFound();

        Livewire::actingAs($adminB)
            ->test(UserManagement::class)
            ->assertDontSee($adminA->email)
            ->assertSee($adminB->email);
    }

    private function businessAdmin(string $businessName, string $email): array
    {
        $business = Business::query()->create([
            'name' => $businessName,
            'slug' => strtolower(str_replace(' ', '-', $businessName)),
            'status' => Business::STATUS_ACTIVE,
        ]);

        $admin = User::factory()->admin()->create([
            'business_id' => $business->id,
            'email' => $email,
        ]);

        return [$business, $admin];
    }
}
