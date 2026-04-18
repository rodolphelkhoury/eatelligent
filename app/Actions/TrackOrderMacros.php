<?php

namespace App\Actions;

use App\Models\Order;

class TrackOrderMacros
{
    /**
     * Returns the macro totals consumed in this single order.
     */
    public function computeForOrder(Order $order): array
    {
        $order->loadMissing('orderItems.product');

        $totals = ['calories' => 0, 'protein_g' => 0.0, 'carbs_g' => 0.0, 'fat_g' => 0.0];

        foreach ($order->orderItems as $item) {
            $p = $item->product;
            $qty = $item->quantity;

            $totals['calories'] += $p->calories * $qty;
            $totals['protein_g'] += (float) $p->protein_g * $qty;
            $totals['carbs_g'] += (float) $p->carbs_g * $qty;
            $totals['fat_g'] += (float) $p->fat_g * $qty;
        }

        return $totals;
    }

    /**
     * Returns the cumulative macros consumed today across all orders.
     */
    public function computeTodayForUser(int $userId): array
    {
        $orders = \App\Models\Order::with('orderItems.product')
            ->where('user_id', $userId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereDate('created_at', today())
            ->get();

        $totals = ['calories' => 0, 'protein_g' => 0.0, 'carbs_g' => 0.0, 'fat_g' => 0.0];

        foreach ($orders as $order) {
            foreach ($order->orderItems as $item) {
                $p = $item->product;
                $qty = $item->quantity;

                $totals['calories'] += $p->calories * $qty;
                $totals['protein_g'] += (float) $p->protein_g * $qty;
                $totals['carbs_g'] += (float) $p->carbs_g * $qty;
                $totals['fat_g'] += (float) $p->fat_g * $qty;
            }
        }

        return $totals;
    }
}
