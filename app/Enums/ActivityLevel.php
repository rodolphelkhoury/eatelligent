<?php

namespace App\Enums;

enum ActivityLevel: string
{
    case NoActivity = 'no_activity';
    case Light = 'light';
    case Moderate = 'moderate';
    case Active = 'active';
    case VeryActive = 'very_active';

    public function multiplier(): float
    {
        return match ($this) {
            self::NoActivity => 1.2,
            self::Light => 1.375,
            self::Moderate => 1.55,
            self::Active => 1.725,
            self::VeryActive => 1.9,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
