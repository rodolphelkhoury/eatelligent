<?php

namespace App\Enums;

enum Goal: string
{
    case LoseWeight = 'lose_weight';
    case Maintain = 'maintain';
    case GainMuscle = 'gain_muscle';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
