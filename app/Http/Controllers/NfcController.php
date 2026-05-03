<?php

namespace App\Http\Controllers;

use App\Actions\PayOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class NfcController extends Controller
{
    /**
     * Handle NFC payment requests.
     */
    public function handle(Request $request)
    {
        $espKeyHeader = $request->header('esp_key');

        if (! env('ESP_KEY') || $espKeyHeader !== env('ESP_KEY')) {
            info('[NFC] Unauthorized request — invalid or missing esp_key.');

            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $cardId = $request->input('id') ?? $request->input('uid');

        info("[NFC] Received tap for card: {$cardId}");

        if (! $cardId) {
            info('[NFC] Request rejected — no card id provided.');

            return response()->json(['status' => 'error', 'message' => 'Missing card id'], 400);
        }

        $user = User::where('card_id', $cardId)->first();

        if (! $user) {
            info("[NFC] No user found for card id: {$cardId}");

            return response()->json(['status' => 'error', 'message' => 'User not found for card id'], 404);
        }

        info("[NFC] Card matched user: {$user->id} ({$user->name})");

        // Find the latest unpaid order belonging to this user
        $order = Order::where('user_id', $user->id)
            ->where('is_paid', false)
            ->latest()
            ->first();

        if (! $order) {
            info("[NFC] No unpaid orders found for user: {$user->id}");

            return response()->json(['status' => 'error', 'message' => 'No unpaid orders found'], 404);
        }

        info("[NFC] Found unpaid order: {$order->id}, total: {$order->total_price}");

        try {
            $transaction = (new PayOrder)->execute($user, $order);

            info("[NFC] Payment successful — transaction: {$transaction->id}, order: {$order->id}, amount: {$order->total_price}");

            return response()->json(['status' => 'ok', 'transaction' => $transaction]);
        } catch (\Exception $e) {
            info("[NFC] Payment failed for user: {$user->id}, order: {$order->id} — {$e->getMessage()}");

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
