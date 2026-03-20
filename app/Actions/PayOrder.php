<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class PayOrder
{
    /**
     * Pay an order using the user's wallet.
     *
     * @throws \Exception on failure
     */
    public function execute(User $user, Order $order): Transaction
    {
        return DB::transaction(function () use ($user, $order) {
            if ($order->user_id !== $user->id) {
                throw new \Exception('Unauthorized to pay this order.');
            }

            if ($order->is_paid) {
                throw new \Exception('Order already paid.');
            }
            // if ($order->status !== OrderStatus::Pending->value) {
            //     throw new \Exception('Only pending orders can be paid.');
            // }

            // Lock the wallet row for update to avoid race conditions
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                ]);
            }

            $amount = (float) $order->total_price;
            $balance = (float) $wallet->balance;

            if ($balance < $amount) {
                throw new \Exception('Insufficient wallet balance.');
            }

            // Deduct balance and create transaction
            $wallet->decrement('balance', $amount);

            $transaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'order_id' => $order->id,
                'amount' => $amount,
                'status' => 'completed',
            ]);

            // Mark order as paid
            $order->is_paid = true;
            $order->save();

            return $transaction;
        });
    }
}
