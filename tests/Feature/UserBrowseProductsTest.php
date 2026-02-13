<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserBrowseProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        Sanctum::actingAs($user, [], 'sanctum-user');
    }

    public function test_browse_all_products(): void
    {
        Product::factory()->count(3)->create(['is_active' => true]);
        Product::factory()->count(2)->create(['is_active' => false]);

        $response = $this->getJson('/api/user/products');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_browse_products_filtered_by_category(): void
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        $product1 = Product::factory()->create(['is_active' => true]);
        $product2 = Product::factory()->create(['is_active' => true]);
        $product3 = Product::factory()->create(['is_active' => true]);

        $category1->products()->attach([$product1->id, $product2->id]);
        $category2->products()->attach([$product3->id]);

        $response = $this->getJson("/api/user/products?category_id={$category1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $product1->id])
            ->assertJsonFragment(['id' => $product2->id])
            ->assertJsonMissing(['id' => $product3->id]);
    }

    public function test_browse_products_with_search(): void
    {
        Product::factory()->create(['name' => 'Protein Bar', 'description' => 'Tasty', 'is_active' => true]);
        Product::factory()->create(['name' => 'Energy Drink', 'description' => 'Protein boost', 'is_active' => true]);
        Product::factory()->create(['name' => 'Snack', 'description' => 'Healthy', 'is_active' => true]);

        $response = $this->getJson('/api/user/products?search=Protein');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['name' => 'Protein Bar'])
            ->assertJsonFragment(['name' => 'Energy Drink'])
            ->assertJsonMissing(['name' => 'Snack']);
    }
}
