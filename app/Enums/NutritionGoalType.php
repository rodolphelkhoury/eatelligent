<?php

namespace App\Enums;

enum NutritionGoalType: string
{
    case BuildMuscle = 'build_muscle';
    case LoseFat = 'lose_fat';
    case Maintain = 'maintain';

    public static function values(): array
    {
        return array_map(fn ($e) => $e->value, self::cases());
    }
}
