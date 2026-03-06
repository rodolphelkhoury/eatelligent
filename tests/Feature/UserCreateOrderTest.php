<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserCreateOrderTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, [], 'sanctum-user');
    }

    public function test_create_order_success(): void
    {
        $user = $this->user;

        $p1 = Product::factory()->create(['price' => 5.50, 'stock' => 10, 'is_active' => true]);
        $p2 = Product::factory()->create(['price' => 3.25, 'stock' => 5, 'is_active' => true]);

        $payload = [
            'items' => [
                ['product_id' => $p1->id, 'quantity' => 2],
                ['product_id' => $p2->id, 'quantity' => 1],
            ],
            'scheduled_time' => '2026-03-06 10:00:00',
        ];

        $response = $this->postJson('/api/user/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['order' => ['id', 'user_id', 'status', 'scheduled_time', 'total_price', 'order_items']]);

        $order = $response->json('order');

        $this->assertEquals($user->id, $order['user_id']);

        $this->assertDatabaseHas('orders', [
            'id' => $order['id'],
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order['id'],
            'product_id' => $p1->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order['id'],
            'product_id' => $p2->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $p1->id,
            'stock' => 8,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $p2->id,
            'stock' => 4,
        ]);
    }

    public function test_create_order_validation_error(): void
    {
        $response = $this->postJson('/api/user/orders', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_create_order_insufficient_stock(): void
    {
        $p = Product::factory()->create(['price' => 10.00, 'stock' => 1, 'is_active' => true]);

        $payload = [
            'items' => [
                ['product_id' => $p->id, 'quantity' => 2],
            ],
            'scheduled_time' => '2026-03-06 09:00:00',
        ];

        $response = $this->postJson('/api/user/orders', $payload);

        $response->assertStatus(400)
            ->assertJsonStructure(['message']);
    }
}
