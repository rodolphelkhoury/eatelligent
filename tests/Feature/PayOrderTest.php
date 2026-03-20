<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayOrderTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, [], 'sanctum-user');
    }

    public function test_pay_order_success(): void
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

        $createResp = $this->postJson('/api/user/orders', $payload);
        $createResp->assertStatus(201);

        $order = $createResp->json('order');

        // ensure wallet has enough balance
        Wallet::create([
            'user_id' => $user->id,
            'balance' => (float) $order['total_price'] + 10,
        ]);

        $payResp = $this->postJson("/api/user/orders/{$order['id']}/pay");

        $payResp->assertStatus(200)
            ->assertJsonStructure(['message', 'order', 'transaction']);

        $this->assertDatabaseHas('orders', [
            'id' => $order['id'],
            'is_paid' => true,
        ]);

        $this->assertDatabaseHas('transactions', [
            'order_id' => $order['id'],
            'amount' => $order['total_price'],
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
        ]);
    }

    public function test_pay_order_insufficient_balance(): void
    {
        $p = Product::factory()->create(['price' => 10.00, 'stock' => 5, 'is_active' => true]);

        $payload = [
            'items' => [
                ['product_id' => $p->id, 'quantity' => 2],
            ],
            'scheduled_time' => '2026-03-06 09:00:00',
        ];

        $createResp = $this->postJson('/api/user/orders', $payload);
        $createResp->assertStatus(201);

        $order = $createResp->json('order');

        // no wallet or insufficient wallet
        $payResp = $this->postJson("/api/user/orders/{$order['id']}/pay");

        $payResp->assertStatus(400)
            ->assertJsonStructure(['message']);
    }

    public function test_pay_order_unauthorized(): void
    {
        $p = Product::factory()->create(['price' => 2.00, 'stock' => 5, 'is_active' => true]);

        $payload = [
            'items' => [
                ['product_id' => $p->id, 'quantity' => 1],
            ],
            'scheduled_time' => '2026-03-06 09:00:00',
        ];

        $createResp = $this->postJson('/api/user/orders', $payload);
        $createResp->assertStatus(201);

        $order = $createResp->json('order');

        $other = User::factory()->create();
        Sanctum::actingAs($other, [], 'sanctum-user');

        $payResp = $this->postJson("/api/user/orders/{$order['id']}/pay");

        $payResp->assertStatus(403)
            ->assertJsonStructure(['message']);
    }

    public function test_pay_order_already_paid(): void
    {
        $user = $this->user;

        $p = Product::factory()->create(['price' => 4.00, 'stock' => 5, 'is_active' => true]);

        $payload = [
            'items' => [
                ['product_id' => $p->id, 'quantity' => 1],
            ],
            'scheduled_time' => '2026-03-06 11:00:00',
        ];

        $createResp = $this->postJson('/api/user/orders', $payload);
        $createResp->assertStatus(201);

        $order = $createResp->json('order');

        // ensure wallet has enough balance
        Wallet::create([
            'user_id' => $user->id,
            'balance' => (float) $order['total_price'] + 5,
        ]);

        // mark order as already paid
        \App\Models\Order::where('id', $order['id'])->update(['is_paid' => true]);

        $payResp = $this->postJson("/api/user/orders/{$order['id']}/pay");

        $payResp->assertStatus(400)
            ->assertJsonStructure(['message']);
    }
}
