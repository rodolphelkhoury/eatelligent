<?php

namespace Tests\Feature;

use App\Models\CafeteriaStaff;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $staff = CafeteriaStaff::factory()->create();

        Sanctum::actingAs($staff, [], 'sanctum-cafeteria-staff');
    }

    public function test_attach_image_to_product(): void
    {
        $product = Product::factory()->create();
        $image = Image::factory()->create();

        $response = $this->postJson("/api/products/{$product->id}/attach-image", [
            'image_id' => $image->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Image attached successfully')
            ->assertJsonPath('product.id', $product->id);

        $this->assertDatabaseHas('images', [
            'id' => $image->id,
            'owner_id' => $product->id,
            'owner_type' => Product::class,
        ]);
    }

    public function test_attach_image_fails_if_product_already_has_image(): void
    {
        $product = Product::factory()->create();

        Image::factory()->create([
            'owner_id' => $product->id,
            'owner_type' => Product::class,
        ]);

        $newImage = Image::factory()->create();

        $response = $this->postJson("/api/products/{$product->id}/attach-image", [
            'image_id' => $newImage->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'This product already has an image.');

        $this->assertDatabaseMissing('images', [
            'id' => $newImage->id,
            'owner_id' => $product->id,
        ]);
    }

    public function test_attach_image_fails_if_image_already_attached(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $image = Image::factory()->create([
            'owner_id' => $product1->id,
            'owner_type' => Product::class,
        ]);

        $response = $this->postJson("/api/products/{$product2->id}/attach-image", [
            'image_id' => $image->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'This image is already attached to another owner.');
    }

    public function test_detach_image_from_product(): void
    {
        $product = Product::factory()->create();

        $image = Image::factory()->create([
            'owner_id' => $product->id,
            'owner_type' => Product::class,
        ]);

        $response = $this->postJson("/api/products/{$product->id}/detach-image", [
            'image_id' => $image->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Image detached successfully');

        $this->assertDatabaseHas('images', [
            'id' => $image->id,
            'owner_id' => null,
            'owner_type' => null,
        ]);
    }

    public function test_detach_image_fails_if_not_belongs_to_product(): void
    {
        $product = Product::factory()->create();
        $otherProduct = Product::factory()->create();

        $image = Image::factory()->create([
            'owner_id' => $otherProduct->id,
            'owner_type' => Product::class,
        ]);

        $response = $this->postJson("/api/products/{$product->id}/detach-image", [
            'image_id' => $image->id,
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Image not found for this product');
    }
}
