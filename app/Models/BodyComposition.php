<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodyComposition extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'weight_kg',
        'height_cm',
        'body_fat_percent',
        'muscle_mass_kg',
        'bmi',
        'visceral_fat_level',
        'water_percent',
        'bone_mass_kg',
        'goal',
        'activity_level',
        'measured_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'body_fat_percent' => 'decimal:2',
            'muscle_mass_kg' => 'decimal:2',
            'bmi' => 'decimal:2',
            'visceral_fat_level' => 'decimal:2',
            'water_percent' => 'decimal:2',
            'bone_mass_kg' => 'decimal:2',
            'measured_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the body composition.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
