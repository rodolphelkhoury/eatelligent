<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNutritionGoal extends Model
{
    protected $fillable = [
        'user_id',
        'calories',
        'protein_g',
        'carbs_g',
        'fat_g',
        'goal',
        'activity_level',
    ];

    protected $casts = [
        'protein_g' => 'decimal:2',
        'carbs_g' => 'decimal:2',
        'fat_g' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
