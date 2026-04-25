<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class CalculateDailyProgress
{
    /**
     * Calculate what the user has eaten today vs. their daily targets.
     *
     * Returns an array with:
     *   - daily_targets:  the user's configured daily targets (from latest profile)
     *   - eaten_today:    raw nutrient totals consumed
     *   - remaining:      how much is still needed to reach each target
     *   - progress:       per-nutrient breakdown (goal / eaten / remaining / reached)
     */
    public function execute(User $user): array
    {
        $profile = $user->latestProfile;

        if (! $profile) {
            return [
                'daily_targets' => [
                    'calories' => 0,
                    'protein_g' => 0,
                    'carbs_g' => 0,
                    'fat_g' => 0,
                ],
                'eaten_today' => ['calories' => 0, 'protein_g' => 0.0, 'carbs_g' => 0.0, 'fat_g' => 0.0],
                'remaining' => ['calories' => 0, 'protein_g' => 0.0, 'carbs_g' => 0.0, 'fat_g' => 0.0],
                'progress' => [],
            ];
        }

        $todayOrders = Order::with('orderItems.product')
            ->where('user_id', $user->id)
            ->whereIn('status', [OrderStatus::Confirmed, OrderStatus::Completed, OrderStatus::ReadyForPickup])
            ->whereDate('created_at', today())
            ->get();

        $eaten = [
            'calories' => 0,
            'protein_g' => 0.0,
            'carbs_g' => 0.0,
            'fat_g' => 0.0,
        ];

        foreach ($todayOrders as $order) {
            foreach ($order->orderItems as $item) {
                $p = $item->product;
                if (! $p) {
                    continue;
                }
                $qty = $item->quantity;

                $eaten['calories'] += ($p->calories ?? 0) * $qty;
                $eaten['protein_g'] += (float) ($p->protein_g ?? 0) * $qty;
                $eaten['carbs_g'] += (float) ($p->carbs_g ?? 0) * $qty;
                $eaten['fat_g'] += (float) ($p->fat_g ?? 0) * $qty;
            }
        }

        $remaining = [
            'calories' => max(0, $profile->daily_calories - $eaten['calories']),
            'protein_g' => max(0, (float) $profile->daily_protein_g - $eaten['protein_g']),
            'carbs_g' => max(0, (float) $profile->daily_carbs_g - $eaten['carbs_g']),
            'fat_g' => max(0, (float) $profile->daily_fat_g - $eaten['fat_g']),
        ];

        $progress = [
            'calories' => [
                'goal' => $profile->daily_calories,
                'eaten' => $eaten['calories'],
                'remaining' => $remaining['calories'],
                'reached' => $eaten['calories'] >= $profile->daily_calories,
            ],
            'protein_g' => [
                'goal' => $profile->daily_protein_g,
                'eaten' => $eaten['protein_g'],
                'remaining' => $remaining['protein_g'],
                'reached' => $eaten['protein_g'] >= (float) $profile->daily_protein_g,
            ],
            'carbs_g' => [
                'goal' => $profile->daily_carbs_g,
                'eaten' => $eaten['carbs_g'],
                'remaining' => $remaining['carbs_g'],
                'reached' => $eaten['carbs_g'] >= (float) $profile->daily_carbs_g,
            ],
            'fat_g' => [
                'goal' => $profile->daily_fat_g,
                'eaten' => $eaten['fat_g'],
                'remaining' => $remaining['fat_g'],
                'reached' => $eaten['fat_g'] >= (float) $profile->daily_fat_g,
            ],
        ];

        return [
            'daily_targets' => [
                'calories' => $profile->daily_calories,
                'protein_g' => $profile->daily_protein_g,
                'carbs_g' => $profile->daily_carbs_g,
                'fat_g' => $profile->daily_fat_g,
            ],
            'eaten_today' => $eaten,
            'remaining' => $remaining,
            'progress' => $progress,
        ];
    }
}
