<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodyComposition extends Model
{
    protected $fillable = [
        'user_id',
        'weight_kg',
        'height_cm',
        'age',
        'gender',
        'activity_level',
        'goal',
        'bmi',
        'bmr',
        'tdee',
        'daily_calories',
        'daily_protein_g',
        'daily_carbs_g',
        'daily_fat_g',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'bmi' => 'decimal:2',
            'bmr' => 'decimal:2',
            'tdee' => 'decimal:2',
            'daily_protein_g' => 'decimal:2',
            'daily_carbs_g' => 'decimal:2',
            'daily_fat_g' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
