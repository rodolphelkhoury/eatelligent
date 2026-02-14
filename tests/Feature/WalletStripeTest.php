<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Tests\TestCase;

class WalletStripeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and authenticate
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, [], 'sanctum-user');

        /*
        |-----------------------------------------------------------
        | Mock Stripe Checkout Session
        |-----------------------------------------------------------
        */
        $mockSession = (object) [
            'id' => 'cs_test_123',
            'url' => 'https://stripe.test/checkout/cs_test_123',
        ];

        $sessionMock = Mockery::mock('alias:'.Session::class);
        $sessionMock
            ->shouldReceive('create')
            ->andReturn($mockSession);

        /*
        |-----------------------------------------------------------
        | Mock Stripe Webhook Signature Verification
        |-----------------------------------------------------------
        */
        $webhookMock = Mockery::mock('alias:'.Webhook::class);
        $webhookMock
            ->shouldReceive('constructEvent')
            ->andReturnUsing(function ($payload, $sigHeader, $secret) {

                // 🔥 FIX: decode JSON because getContent() returns string
                $decoded = json_decode($payload, true);

                return (object) [
                    'type' => $decoded['type'] ?? '',
                    'data' => (object) [
                        'object' => (object) ($decoded['data']['object'] ?? []),
                    ],
                ];
            });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function user_can_create_checkout_session()
    {
        $response = $this->postJson('/api/wallet/checkout', [
            'amount' => 50,
            'success_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['checkout_url']);

        $this->assertDatabaseHas('transactions', [
            'amount' => 50,
            'stripe_session_id' => 'cs_test_123',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function webhook_completes_transaction_and_updates_wallet()
    {
        $user = $this->user;

        $wallet = Wallet::factory()->for($user)->create(['balance' => 0]);

        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'amount' => 100,
            'stripe_session_id' => 'cs_test_123',
            'status' => 'pending',
        ]);

        $payload = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'payment_status' => 'paid',
                    'metadata' => [
                        'wallet_id' => $wallet->id,
                        'amount' => 100,
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/stripe/webhook', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 100,
        ]);
    }

    /** @test */
    public function webhook_does_not_update_wallet_if_not_paid()
    {
        $user = $this->user;

        $wallet = Wallet::factory()->for($user)->create(['balance' => 0]);

        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'amount' => 100,
            'stripe_session_id' => 'cs_test_123',
            'status' => 'pending',
        ]);

        $payload = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'payment_status' => 'unpaid',
                    'metadata' => [
                        'wallet_id' => $wallet->id,
                        'amount' => 100,
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/stripe/webhook', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 0,
        ]);
    }
}
