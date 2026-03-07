<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\CafeteriaStaff;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CafeteriaStaffOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    protected $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = CafeteriaStaff::factory()->create();
        Sanctum::actingAs($this->staff, [], 'sanctum-cafeteria-staff');
    }

    public function test_confirm_order_success(): void
    {
        $user = User::factory()->create();
        $p = Product::factory()->create(['price' => 2.00, 'stock' => 5, 'is_active' => true]);

        $order = Order::create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending->value,
            'scheduled_time' => '2026-03-07 09:00:00',
            'total_price' => 4.00,
        ]);

        OrderItem::create(['order_id' => $order->id, 'product_id' => $p->id, 'quantity' => 2, 'unit_price' => $p->price]);

        $response = $this->patchJson('/api/cafeteria-staff/orders/'.$order->id.'/confirm');

        $response->assertStatus(200)
            ->assertJsonStructure(['order' => ['id', 'user_id', 'status', 'scheduled_time', 'total_price', 'order_items']]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Confirmed->value,
        ]);
    }

    public function test_confirm_order_invalid_status(): void
    {
        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'status' => OrderStatus::Confirmed->value,
            'scheduled_time' => '2026-03-07 09:00:00',
            'total_price' => 10.00,
        ]);

        $response = $this->patchJson('/api/cafeteria-staff/orders/'.$order->id.'/confirm');

        $response->assertStatus(400)->assertJsonStructure(['message']);
    }

    public function test_complete_order_success(): void
    {
        $user = User::factory()->create();
        $p = Product::factory()->create(['price' => 3.00, 'stock' => 5, 'is_active' => true]);

        $order = Order::create([
            'user_id' => $user->id,
            'status' => OrderStatus::Confirmed->value,
            'scheduled_time' => '2026-03-07 10:00:00',
            'total_price' => 3.00,
        ]);

        OrderItem::create(['order_id' => $order->id, 'product_id' => $p->id, 'quantity' => 1, 'unit_price' => $p->price]);

        $response = $this->patchJson('/api/cafeteria-staff/orders/'.$order->id.'/complete');

        $response->assertStatus(200)
            ->assertJsonStructure(['order' => ['id', 'user_id', 'status', 'scheduled_time', 'total_price', 'order_items']]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Completed->value,
        ]);
    }

    public function test_complete_order_invalid_status(): void
    {
        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending->value,
            'scheduled_time' => '2026-03-07 10:00:00',
            'total_price' => 3.00,
        ]);

        $response = $this->patchJson('/api/cafeteria-staff/orders/'.$order->id.'/complete');

        $response->assertStatus(400)->assertJsonStructure(['message']);
    }

    public function test_cancel_order_success(): void
    {
        $user = User::factory()->create();
        $p = Product::factory()->create(['price' => 4.00, 'stock' => 5, 'is_active' => true]);

        $order = Order::create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending->value,
            'scheduled_time' => '2026-03-07 11:00:00',
            'total_price' => 8.00,
        ]);

        OrderItem::create(['order_id' => $order->id, 'product_id' => $p->id, 'quantity' => 2, 'unit_price' => $p->price]);

        $response = $this->patchJson('/api/cafeteria-staff/orders/'.$order->id.'/cancel');

        $response->assertStatus(200)
            ->assertJsonStructure(['order' => ['id', 'user_id', 'status', 'scheduled_time', 'total_price', 'order_items']]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
        ]);
    }

    public function test_cancel_order_invalid_status(): void
    {
        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'status' => OrderStatus::Completed->value,
            'scheduled_time' => '2026-03-07 11:00:00',
            'total_price' => 8.00,
        ]);

        $response = $this->patchJson('/api/cafeteria-staff/orders/'.$order->id.'/cancel');

        $response->assertStatus(400)->assertJsonStructure(['message']);
    }
}
