<?php

namespace Tests\Feature;

use App\Models\CafeteriaStaff;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $staff = CafeteriaStaff::factory()->create();

        Sanctum::actingAs($staff, [], 'sanctum-cafeteria-staff');
    }

    public function test_create_category_success(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'Beverages',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Beverages');

        $this->assertDatabaseHas('categories', ['name' => 'Beverages']);
    }

    public function test_create_category_duplicate_name(): void
    {
        Category::create(['name' => 'Beverages']);

        $response = $this->postJson('/api/categories', [
            'name' => 'Beverages',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'The name has already been taken.');
    }

    public function test_create_category_missing_name(): void
    {
        $response = $this->postJson('/api/categories', []);

        $response->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    }

    public function test_list_categories(): void
    {
        Category::create(['name' => 'Snacks']);
        Category::create(['name' => 'Drinks']);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_show_category(): void
    {
        $category = Category::create(['name' => 'Desserts']);

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Desserts');
    }

    public function test_update_category_success(): void
    {
        $category = Category::create(['name' => 'Old Name']);

        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'New Name');

        $this->assertDatabaseHas('categories', ['name' => 'New Name']);
    }

    public function test_update_category_duplicate_name(): void
    {
        Category::create(['name' => 'Snacks']);
        $category = Category::create(['name' => 'Drinks']);

        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => 'Snacks',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'The name has already been taken.');
    }

    public function test_delete_category(): void
    {
        $category = Category::create(['name' => 'To Delete']);

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Category deleted successfully');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_attach_products_to_category(): void
    {
        $category = Category::factory()->create();
        $products = Product::factory()->count(3)->create();

        $response = $this->postJson("/api/categories/{$category->id}/attach-products", [
            'product_ids' => $products->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('category.products.0.id', $products[0]->id);

        foreach ($products as $product) {
            $this->assertDatabaseHas('category_product', [
                'category_id' => $category->id,
                'product_id' => $product->id,
            ]);
        }
    }

    public function test_detach_products_from_category(): void
    {
        $category = Category::factory()->create();
        $products = Product::factory()->count(2)->create();

        $category->products()->attach($products->pluck('id'));

        $response = $this->postJson("/api/categories/{$category->id}/detach-products", [
            'product_ids' => [$products[0]->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'category.products');

        $this->assertDatabaseMissing('category_product', [
            'category_id' => $category->id,
            'product_id' => $products[0]->id,
        ]);

        $this->assertDatabaseHas('category_product', [
            'category_id' => $category->id,
            'product_id' => $products[1]->id,
        ]);
    }
}
