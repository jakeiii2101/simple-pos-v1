<?php

namespace Tests\Feature\Categories;

use App\Livewire\Categories\CategoryList;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_categories_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/categories')
            ->assertOk();
    }

    public function test_cashier_cannot_view_categories_page(): void
    {
        $cashier = User::factory()->create();

        $this->actingAs($cashier)
            ->get('/categories')
            ->assertForbidden();
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CategoryList::class)
            ->call('create')
            ->set('name', 'Beverages')
            ->set('description', 'Drinks and refreshments')
            ->set('status', Category::STATUS_ACTIVE)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Beverages',
            'description' => 'Drinks and refreshments',
            'status' => Category::STATUS_ACTIVE,
        ]);
    }

    public function test_category_name_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Category::query()->create([
            'name' => 'Food',
            'description' => null,
            'status' => Category::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin);

        Livewire::test(CategoryList::class)
            ->call('create')
            ->set('name', 'Food')
            ->set('status', Category::STATUS_ACTIVE)
            ->call('save')
            ->assertHasErrors(['name' => 'unique']);
    }

    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::query()->create([
            'name' => 'Food',
            'description' => 'Old description',
            'status' => Category::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin);

        Livewire::test(CategoryList::class)
            ->call('edit', $category->id)
            ->set('name', 'Grocery')
            ->set('description', 'Updated description')
            ->set('status', Category::STATUS_INACTIVE)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Grocery',
            'description' => 'Updated description',
            'status' => Category::STATUS_INACTIVE,
        ]);
    }

    public function test_admin_can_delete_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::query()->create([
            'name' => 'Others',
            'description' => null,
            'status' => Category::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin);

        Livewire::test(CategoryList::class)
            ->call('delete', $category->id);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_cashier_cannot_invoke_categories_component_directly(): void
    {
        $cashier = User::factory()->create();
        $this->actingAs($cashier);

        Livewire::test(CategoryList::class)
            ->assertForbidden();
    }
}
