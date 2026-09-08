<?php

namespace Tests\Feature;

use App\Livewire\Products\ProductList;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_products_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/products')
            ->assertOk();
    }

    public function test_cashier_cannot_access_products_page(): void
    {
        $cashier = User::factory()->create();

        $this->actingAs($cashier)
            ->get('/products')
            ->assertForbidden();
    }

    public function test_admin_can_create_product(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::query()->create([
            'name' => 'Baby Care',
            'description' => 'Baby products',
            'status' => Category::STATUS_ACTIVE,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductList::class)
            ->set('categoryId', $category->id)
            ->set('sku', 'BABY-001')
            ->set('barcode', '480000000001')
            ->set('name', 'Baby Powder')
            ->set('costPrice', '50.00')
            ->set('sellingPrice', '65.00')
            ->set('stockQuantity', 20)
            ->set('lowStockLevel', 5)
            ->set('status', Product::STATUS_ACTIVE)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::query()->where('sku', 'BABY-001')->firstOrFail();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'barcode' => '480000000001',
            'name' => 'Baby Powder',
            'category_id' => $category->id,
            'stock_quantity' => 20,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'product.created',
            'auditable_type' => $product->getMorphClass(),
            'auditable_id' => $product->id,
        ]);
    }

    public function test_duplicate_sku_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::query()->create([
            'name' => 'Baby Care',
            'status' => Category::STATUS_ACTIVE,
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'sku' => 'BABY-001',
            'name' => 'Existing Product',
            'cost_price' => 10,
            'selling_price' => 15,
            'stock_quantity' => 1,
            'low_stock_level' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductList::class)
            ->set('categoryId', $category->id)
            ->set('sku', 'BABY-001')
            ->set('name', 'Duplicate Product')
            ->set('costPrice', '10.00')
            ->set('sellingPrice', '20.00')
            ->set('stockQuantity', 1)
            ->set('lowStockLevel', 1)
            ->call('save')
            ->assertHasErrors(['sku' => 'unique']);
    }

    public function test_product_update_price_change_and_delete_are_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::query()->create([
            'name' => 'Baby Care',
            'status' => Category::STATUS_ACTIVE,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'sku' => 'BABY-001',
            'name' => 'Baby Powder',
            'cost_price' => 50,
            'selling_price' => 65,
            'stock_quantity' => 20,
            'low_stock_level' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductList::class)
            ->call('edit', $product->id)
            ->set('name', 'Baby Powder 200g')
            ->set('sellingPrice', '70.00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Baby Powder 200g',
            'selling_price' => '70.00',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'product.updated',
            'auditable_id' => $product->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'product.price_changed',
            'auditable_id' => $product->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductList::class)
            ->call('delete', $product->id);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'product.deleted',
            'auditable_id' => $product->id,
        ]);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::query()->create([
            'name' => 'Baby Care',
            'status' => Category::STATUS_ACTIVE,
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'sku' => 'BABY-001',
            'name' => 'Baby Powder',
            'cost_price' => 50,
            'selling_price' => 65,
            'stock_quantity' => 20,
            'low_stock_level' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Categories\CategoryList::class)
            ->call('delete', $category->id);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
