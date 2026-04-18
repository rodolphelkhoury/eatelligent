<?php

namespace App\Enums;

enum ActivityLevel: string
{
    case Sedentary = 'sedentary';   // desk job, no sports
    case Light = 'light';       // 1-2x/week sport
    case Moderate = 'moderate';    // 3-4x/week sport
    case Active = 'active';      // 5-6x/week sport
    case VeryActive = 'very_active'; // daily intense training

    // Mifflin-St Jeor activity multiplier
    public function multiplier(): float
    {
        return match ($this) {
            self::Sedentary => 1.2,
            self::Light => 1.375,
            self::Moderate => 1.55,
            self::Active => 1.725,
            self::VeryActive => 1.9,
        };
    }

    public static function values(): array
    {
        return array_map(fn ($e) => $e->value, self::cases());
    }
}
