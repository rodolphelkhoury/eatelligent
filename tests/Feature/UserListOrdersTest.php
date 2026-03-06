<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserListOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, [], 'sanctum-user');
    }

    public function test_list_orders_success(): void
    {
        $user = $this->user;

        $p1 = Product::factory()->create(['price' => 5.50, 'stock' => 10, 'is_active' => true]);
        $p2 = Product::factory()->create(['price' => 3.25, 'stock' => 5, 'is_active' => true]);

        $order1 = Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'scheduled_time' => '2026-03-06 10:00:00',
            'total_price' => 14.25,
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $p1->id,
            'quantity' => 2,
            'unit_price' => $p1->price,
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $p2->id,
            'quantity' => 1,
            'unit_price' => $p2->price,
        ]);

        $order2 = Order::create([
            'user_id' => $user->id,
            'status' => 'completed',
            'scheduled_time' => '2026-03-06 11:00:00',
            'total_price' => 11.00,
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $p1->id,
            'quantity' => 2,
            'unit_price' => $p1->price,
        ]);

        $response = $this->getJson('/api/user/orders');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                [
                    'id', 'user_id', 'status', 'scheduled_time', 'total_price', 'order_items',
                ],
            ]);

        $this->assertDatabaseHas('orders', ['id' => $order1->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('orders', ['id' => $order2->id, 'user_id' => $user->id]);
    }
}
