<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Admin::factory()->create();

        Sanctum::actingAs($admin, [], 'sanctum-admin');
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
}
