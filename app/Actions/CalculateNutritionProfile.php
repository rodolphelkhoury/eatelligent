<?php

namespace App\Actions;

class CalculateNutritionProfile
{
    public function execute(
        float $weightKg,
        float $heightCm,
        int $age,
        string $gender,
        string $activityLevel,
        string $goal
    ): array {
        // --- BMI ---
        $heightM = $heightCm / 100;
        $bmi = round($weightKg / ($heightM ** 2), 2);

        // --- BMR (Mifflin-St Jeor) ---
        // Male:   BMR = 10*weight(kg) + 6.25*height(cm) - 5*age + 5
        // Female: BMR = 10*weight(kg) + 6.25*height(cm) - 5*age - 161
        $bmr = (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age);
        $bmr += ($gender === 'male') ? 5 : -161;
        $bmr = round($bmr, 2);

        // --- TDEE ---
        $multiplier = \App\Enums\ActivityLevel::from($activityLevel)->multiplier();
        $tdee = round($bmr * $multiplier, 2);

        // --- Daily calorie target based on goal ---
        $dailyCalories = match ($goal) {
            'lose_weight' => (int) round($tdee * 0.80),  // 20% deficit
            'maintain' => (int) round($tdee),
            'gain_muscle' => (int) round($tdee * 1.10),  // 10% surplus
        };

        // --- Macro splits ---
        // Protein: 2.0g/kg for lose_weight & gain_muscle, 1.6g/kg for maintain
        $proteinPerKg = ($goal === 'maintain') ? 1.6 : 2.0;
        $dailyProtein = round($weightKg * $proteinPerKg, 2);

        // Fat: 25% of daily calories (9 kcal/g)
        $dailyFat = round(($dailyCalories * 0.25) / 9, 2);

        // Carbs: fill remaining calories (4 kcal/g)
        $proteinCalories = $dailyProtein * 4;
        $fatCalories = $dailyFat * 9;
        $carbCalories = $dailyCalories - $proteinCalories - $fatCalories;
        $dailyCarbs = round(max(0, $carbCalories) / 4, 2);

        return [
            'bmi' => $bmi,
            'bmr' => $bmr,
            'tdee' => $tdee,
            'daily_calories' => $dailyCalories,
            'daily_protein_g' => $dailyProtein,
            'daily_carbs_g' => $dailyCarbs,
            'daily_fat_g' => $dailyFat,
        ];
    }
}
