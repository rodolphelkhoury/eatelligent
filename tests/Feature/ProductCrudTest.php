<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin, [], 'sanctum-admin');
    }

    public function test_create_product_success(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'Protein Bar',
            'description' => 'Healthy snack',
            'price' => 2.50,
            'calories' => 180,
            'protein_g' => 12,
            'carbs_g' => 20,
            'fat_g' => 6,
            'stock' => 30,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Protein Bar');

        $this->assertDatabaseHas('products', ['name' => 'Protein Bar']);
    }

    public function test_create_product_missing_name(): void
    {
        $response = $this->postJson('/api/products', []);

        $response->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    }

    public function test_list_products(): void
    {
        Product::factory()->count(2)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)->assertJsonCount(2);
    }

    public function test_show_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $product->id);
    }

    public function test_update_product(): void
    {
        $product = Product::factory()->create(['name' => 'Old']);

        $response = $this->putJson("/api/products/{$product->id}", [
            'name' => 'New',
            'description' => 'Updated',
            'price' => 5,
            'calories' => 200,
            'protein_g' => 20,
            'carbs_g' => 25,
            'fat_g' => 8,
            'stock' => 10,
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'New');

        $this->assertDatabaseHas('products', ['name' => 'New']);
    }

    public function test_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Product deleted successfully');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_attach_categories_to_product(): void
    {
        $product = Product::factory()->create();
        $categories = Category::factory()->count(3)->create();

        $response = $this->postJson("/api/products/{$product->id}/attach-categories", [
            'category_ids' => $categories->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('product.categories.0.id', $categories[0]->id);

        foreach ($categories as $category) {
            $this->assertDatabaseHas('category_product', [
                'category_id' => $category->id,
                'product_id' => $product->id,
            ]);
        }
    }

    public function test_detach_categories_from_product(): void
    {
        $product = Product::factory()->create();
        $categories = Category::factory()->count(2)->create();

        $product->categories()->attach($categories->pluck('id'));

        $response = $this->postJson("/api/products/{$product->id}/detach-categories", [
            'category_ids' => [$categories[0]->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'product.categories');

        $this->assertDatabaseMissing('category_product', [
            'category_id' => $categories[0]->id,
            'product_id' => $product->id,
        ]);

        $this->assertDatabaseHas('category_product', [
            'category_id' => $categories[1]->id,
            'product_id' => $product->id,
        ]);
    }
}
