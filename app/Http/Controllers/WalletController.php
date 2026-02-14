<?php

namespace App\Http\Controllers;

use App\Http\Requests\Wallet\CheckoutWalletRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;

use function Laravel\Prompts\info;

class WalletController extends Controller
{
    /**
     * Create Stripe Checkout session for wallet top-up
     */
    public function checkout(CheckoutWalletRequest $request)
    {
        $user = $request->user();
        $amount = (float) $request->amount;

        $wallet = $user->wallet()->firstOrCreate([
            'user_id' => $user->id,
        ]);

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Wallet Top-Up',
                    ],
                    'unit_amount' => (int) ($amount * 100),
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
            ],
            'success_url' => $request->success_url,
            'cancel_url' => $request->cancel_url,
        ]);

        Transaction::create([
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'stripe_session_id' => $session->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'checkout_url' => $session->url,
        ]);
    }

    /**
     * Handle Stripe webhook
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        info($payload);
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $secret
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid webhook'], 400);
        }

        if ($event->type === 'checkout.session.completed') {

            $session = $event->data->object;

            if ($session->payment_status !== 'paid') {
                return response()->json(['status' => 'not paid']);
            }

            $transaction = Transaction::where(
                'stripe_session_id',
                $session->id
            )->first();

            if (! $transaction || $transaction->status !== 'pending') {
                return response()->json(['status' => 'already processed']);
            }

            DB::transaction(function () use ($transaction) {

                $wallet = $transaction->wallet;

                $wallet->increment('balance', $transaction->amount);

                $transaction->update([
                    'status' => 'completed',
                ]);
            });
        }

        if ($event->type === 'checkout.session.expired') {

            $session = $event->data->object;

            Transaction::where(
                'stripe_session_id',
                $session->id
            )->update([
                'status' => 'expired',
            ]);
        }

        return response()->json(['status' => 'success']);
    }
}
