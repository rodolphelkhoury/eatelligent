<?php

namespace App\Http\Requests\BodyComposition;

use App\Enums\ActivityLevel;
use App\Enums\NutritionGoalType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBodyCompositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'weight_kg' => 'required|numeric|min:1',
            'height_cm' => 'required|numeric|min:1',
            'age' => 'required|integer|min:1',
            'activity_level' => ['required', Rule::in(ActivityLevel::values())],
            'goal' => ['required', Rule::in(NutritionGoalType::values())],

            'body_fat_percent' => 'nullable|numeric|min:0|max:100',
            'muscle_mass_kg' => 'nullable|numeric|min:0',
            'visceral_fat_level' => 'nullable|numeric|min:0',
            'water_percent' => 'nullable|numeric|min:0|max:100',
            'bone_mass_kg' => 'nullable|numeric|min:0',
            'measured_at' => 'required|date',
        ];
    }
}
